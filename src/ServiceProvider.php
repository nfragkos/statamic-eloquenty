<?php

namespace Eloquenty;

use Eloquenty\Commands\ImportEntries;
use Eloquenty\Entries\CollectionRepository;
use Eloquenty\Entries\EntryModel;
use Eloquenty\Entries\EntryQueryBuilder;
use Eloquenty\Entries\EntryRepository;
use Eloquenty\Facades\Eloquenty as EloquentyFacade;
use Eloquenty\Helpers\Eloquenty as EloquentyHelper;
use Eloquenty\Http\Middleware\EloquentyMiddleware;
use Eloquenty\Listeners\UpdateEntriesUri;
use Eloquenty\Providers\RouteServiceProvider;
use Eloquenty\Tags\Collection as CollectionTag;
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
                ->can('index', [\Statamic\Contracts\Entries\Collection::class])
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
        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);

        $this->app->bind(EntryQueryBuilder::class, function () {
            return new EntryQueryBuilder(EntryModel::query());
        });

        $this->app->bind(EntryRepository::class, function ($app) {
            return new EntryRepository($app['stache']);
        });

        $this->app->bind('eloquenty', function () {
            return new EloquentyHelper();
        });
        $this->app->alias('Eloquenty', EloquentyFacade::class);
    }
}
