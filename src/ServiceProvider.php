<?php

namespace Nfragkos\Eloquenty;

use Nfragkos\Eloquenty\Commands\ImportEntries;
use Nfragkos\Eloquenty\Entries\CollectionRepository;
use Nfragkos\Eloquenty\Entries\DbEntryQueryBuilder;
use Nfragkos\Eloquenty\Entries\DbEntryRepository;
use Nfragkos\Eloquenty\Facades\Eloquenty as EloquentyFacade;
use Nfragkos\Eloquenty\Helpers\Eloquenty as EloquentyHelper;
use Nfragkos\Eloquenty\Http\Middleware\EloquentyMiddleware;
use Nfragkos\Eloquenty\Listeners\UpdateEntriesUri;
use Nfragkos\Eloquenty\Models\Entry as EntryModel;
use Nfragkos\Eloquenty\Providers\RouteServiceProvider;
use Nfragkos\Eloquenty\Tags\Collection as CollectionTag;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Events\CollectionSaved;
use Statamic\Facades\Collection;
use Statamic\Facades\CP\Nav;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'cp' => __DIR__ . '/../routes/cp.php',
        'web' => __DIR__ . '/../routes/web.php',
    ];

    protected $listen = [
        CollectionSaved::class => [
            UpdateEntriesUri::class,
        ],
    ];

    protected $middlewareGroups = [
        'statamic.cp.authenticated' => [
            EloquentyMiddleware::class,
        ],
    ];

    protected $tags = [
        CollectionTag::class,
    ];

    public function boot()
    {
        parent::boot();

        $this->loadViewsFrom(__DIR__ . '/../resources/views/', 'eloquenty');
        $this->mergeConfigFrom(__DIR__ . '/../config/eloquenty.php', 'eloquenty');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/eloquenty.php' => config_path('eloquenty.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../database/migrations/2021_01_12_000000_create_eloquenty_tables.php' =>
                    database_path('migrations/2021_01_12_000000_create_eloquenty_tables.php'),
            ], 'migrations');
        }

        $this->bootNavigation();
        $this->registerServiceProviders();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportEntries::class,
            ]);
        }
    }

    private function bootNavigation(): void
    {
        $collections = EloquentyFacade::collections();

        Nav::extend(function (\Statamic\CP\Navigation\Nav $nav) use ($collections) {
            $children = [];

            foreach ($collections as $collection) {
                $children[] = $nav->item(Collection::find($collection)->title())->route('eloquenty.collections.show', [$collection]);
            }

            $nav->create('Eloquenty')
                ->icon('collections')
                ->section('Content')
                ->route('eloquenty.collections.index')
                ->can('view')
                ->children($children);
        });
    }

    private function registerServiceProviders(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function register()
    {
        $this->registerEntries();
    }

    protected function registerEntries()
    {
        $this->app->bind('eloquenty', function () {
            return new EloquentyHelper();
        });
        $this->app->alias('Eloquenty', EloquentyFacade::class);

        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);

        $this->app->bind('eloquenty.entries.model', function () {
            return EntryModel::class;
        });

        $this->app->bind(DbEntryQueryBuilder::class, function () {
            return new DbEntryQueryBuilder(
                app('eloquenty.entries.model')::query()
            );
        });

        $this->app->bind(DbEntryRepository::class, function ($app) {
            return new DbEntryRepository($app['stache']);
        });
    }
}
