<?php

namespace Eloquenty;

use Eloquenty\Commands\ImportEntries;
use Eloquenty\Entries\CollectionRepository;
use Eloquenty\Entries\EntryModel;
use Eloquenty\Entries\EntryQueryBuilder;
use Eloquenty\Entries\EntryRepository;
use Eloquenty\Facades\Eloquenty as EloquentyFacade;
use Eloquenty\Facades\EloquentyEntry;
use Eloquenty\Fieldtypes\EloquentyEntries;
use Eloquenty\Helpers\Eloquenty as EloquentyHelper;
use Eloquenty\Http\Middleware\EloquentyMiddleware;
use Eloquenty\Listeners\UpdateEntriesUri;
use Eloquenty\Providers\EventServiceProvider;
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

    public function bootAddon()
    {
        parent::bootAddon();

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
        $this->registerFieldtypes();

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
                $children[] = $nav->item(Collection::find($collection)->title())->route(
                    'eloquenty.collections.show',
                    [$collection]
                );
            }

            $nav->create('Eloquenty')
                ->icon(
                    '<svg xmlns="http://www.w3.org/2000/svg" x="0" y="0" viewBox="0 0 56 56"><g><path d="M52.261,33.454l-2.85-2.85c-1.164-1.164-3.058-1.164-4.222,0L29.799,45.995l-2.122,7.779l-0.519,0.519 c-0.388,0.388-0.389,1.014-0.006,1.405l-0.005,0.02l0.019-0.005C27.361,55.903,27.613,56,27.866,56 c0.256,0,0.512-0.098,0.707-0.293l0.52-0.52l7.778-2.121l15.391-15.391C53.425,36.512,53.425,34.618,52.261,33.454z M32.109,46.515 l10.243-10.243l4.243,4.243L36.351,50.758L32.109,46.515z M31.206,48.44l3.22,3.22l-4.428,1.208L31.206,48.44z M50.847,36.262 l-2.839,2.839l-4.243-4.243l2.839-2.839c0.385-0.385,1.009-0.385,1.394,0l2.849,2.85C51.231,35.252,51.231,35.878,50.847,36.262z" /> <path d="M22.941,52.818C11.827,51.984,5.35,48.6,4.898,45.838C4.89,45.793,4.88,45.75,4.866,45.707v-8.414 c0.018,0.016,0.039,0.032,0.057,0.048c0.232,0.205,0.47,0.409,0.738,0.605C9.433,40.791,16.961,43,27.866,43c0.552,0,1-0.447,1-1 s-0.448-1-1-1c-0.827,0-1.637-0.016-2.432-0.044c-0.482-0.018-0.947-0.049-1.417-0.076c-0.297-0.017-0.603-0.028-0.895-0.049 c-0.624-0.045-1.23-0.103-1.829-0.165c-0.12-0.013-0.246-0.021-0.365-0.034c-0.686-0.075-1.354-0.161-2.005-0.256 c-0.024-0.003-0.049-0.006-0.072-0.009c-3.379-0.499-6.279-1.26-8.548-2.165c-0.017-0.007-0.035-0.013-0.051-0.02 c-0.414-0.167-0.803-0.338-1.174-0.514c-0.047-0.022-0.097-0.044-0.144-0.067c-0.333-0.16-0.64-0.325-0.935-0.491 c-0.073-0.042-0.15-0.083-0.221-0.125c-0.256-0.15-0.49-0.304-0.714-0.458c-0.089-0.062-0.18-0.123-0.263-0.185 c-0.188-0.139-0.358-0.28-0.52-0.421c-0.092-0.08-0.183-0.16-0.266-0.241c-0.132-0.128-0.249-0.257-0.358-0.386 c-0.08-0.094-0.156-0.188-0.224-0.283c-0.087-0.121-0.161-0.242-0.227-0.363c-0.055-0.101-0.104-0.202-0.146-0.303 c-0.048-0.119-0.086-0.238-0.114-0.356c-0.039-0.163-0.08-0.327-0.08-0.488v-8.207c0.018,0.016,0.039,0.032,0.057,0.048 c0.232,0.205,0.47,0.409,0.738,0.605C9.433,28.791,16.961,31,27.866,31c10.88,0,18.396-2.199,22.177-5.034 c0.29-0.212,0.55-0.431,0.799-0.653c0.008-0.007,0.017-0.014,0.024-0.021V29c0,0.553,0.448,1,1,1s1-0.447,1-1v-7v-0.5v-12V9 c0-0.182-0.062-0.343-0.146-0.49C51.563,4.22,42.944,0,27.866,0S4.169,4.22,3.012,8.51C2.927,8.657,2.866,8.818,2.866,9v0.5v12V22 v11.5V34v12c0,0.161,0.042,0.313,0.115,0.448c1.139,4.833,10.691,7.68,19.811,8.364c0.025,0.002,0.051,0.003,0.076,0.003 c0.519,0,0.957-0.399,0.996-0.925C23.905,53.34,23.492,52.859,22.941,52.818z M5.467,13.797c0.3,0.236,0.624,0.469,0.975,0.696 c0.073,0.047,0.155,0.093,0.231,0.14c0.294,0.183,0.605,0.362,0.932,0.538c0.121,0.065,0.242,0.129,0.367,0.193 c0.365,0.186,0.748,0.366,1.151,0.542c0.066,0.029,0.126,0.059,0.193,0.087c0.469,0.199,0.967,0.389,1.485,0.572 c0.143,0.051,0.293,0.099,0.44,0.149c0.412,0.139,0.838,0.272,1.279,0.401c0.159,0.046,0.315,0.094,0.478,0.139 c0.585,0.162,1.189,0.316,1.823,0.458c0.087,0.02,0.181,0.037,0.269,0.056c0.559,0.122,1.139,0.235,1.735,0.34 c0.202,0.036,0.407,0.07,0.613,0.104c0.567,0.093,1.151,0.179,1.75,0.257c0.154,0.02,0.301,0.042,0.457,0.062 c0.744,0.09,1.514,0.167,2.305,0.233c0.195,0.016,0.398,0.028,0.596,0.042c0.633,0.046,1.28,0.084,1.942,0.114 c0.241,0.011,0.481,0.022,0.727,0.03c0.863,0.03,1.741,0.05,2.65,0.05s1.788-0.021,2.65-0.05c0.245-0.008,0.485-0.02,0.727-0.03 c0.662-0.03,1.309-0.068,1.942-0.114c0.198-0.015,0.4-0.026,0.596-0.042c0.791-0.065,1.561-0.143,2.305-0.233 c0.156-0.019,0.303-0.042,0.457-0.062c0.599-0.078,1.182-0.164,1.75-0.257c0.206-0.034,0.411-0.068,0.613-0.104 c0.596-0.105,1.176-0.218,1.735-0.34c0.088-0.019,0.182-0.036,0.269-0.056c0.634-0.142,1.238-0.296,1.823-0.458 c0.163-0.045,0.319-0.092,0.478-0.139c0.441-0.128,0.867-0.262,1.279-0.401c0.147-0.05,0.297-0.098,0.44-0.149 c0.518-0.184,1.017-0.374,1.485-0.572c0.067-0.028,0.127-0.059,0.193-0.087c0.403-0.176,0.786-0.356,1.151-0.542 c0.125-0.064,0.247-0.128,0.367-0.193c0.327-0.175,0.638-0.354,0.932-0.538c0.076-0.047,0.158-0.093,0.231-0.14 c0.351-0.227,0.675-0.459,0.975-0.696c0.075-0.06,0.142-0.12,0.215-0.18c0.13-0.108,0.267-0.215,0.387-0.324V21.5 c0,0.163-0.041,0.327-0.08,0.491c-0.028,0.117-0.065,0.233-0.112,0.351c-0.042,0.103-0.092,0.206-0.148,0.309 c-0.066,0.119-0.138,0.238-0.223,0.357c-0.069,0.096-0.147,0.192-0.228,0.289c-0.108,0.127-0.223,0.254-0.353,0.38 c-0.085,0.083-0.178,0.165-0.272,0.247c-0.16,0.139-0.327,0.278-0.513,0.415c-0.086,0.064-0.18,0.127-0.271,0.19 c-0.222,0.153-0.453,0.305-0.706,0.453c-0.074,0.044-0.153,0.087-0.23,0.13c-0.292,0.165-0.597,0.328-0.926,0.487 c-0.049,0.023-0.103,0.047-0.153,0.071c-0.369,0.174-0.754,0.345-1.166,0.51c-0.019,0.007-0.039,0.015-0.058,0.022 c-2.269,0.904-5.166,1.665-8.543,2.164c-0.024,0.003-0.05,0.006-0.074,0.01c-0.651,0.095-1.318,0.181-2.003,0.256 c-0.12,0.013-0.246,0.022-0.367,0.034c-0.599,0.062-1.204,0.12-1.827,0.165c-0.293,0.021-0.599,0.032-0.897,0.049 c-0.469,0.027-0.934,0.059-1.416,0.076C29.503,28.984,28.693,29,27.866,29s-1.637-0.016-2.432-0.044 c-0.482-0.018-0.947-0.049-1.417-0.076c-0.297-0.017-0.603-0.028-0.895-0.049c-0.624-0.045-1.23-0.103-1.829-0.165 c-0.12-0.013-0.246-0.021-0.365-0.034c-0.686-0.075-1.354-0.161-2.005-0.256c-0.024-0.003-0.049-0.006-0.072-0.009 c-3.379-0.499-6.279-1.26-8.548-2.165c-0.017-0.007-0.035-0.013-0.051-0.02c-0.414-0.167-0.803-0.338-1.174-0.514 c-0.047-0.022-0.097-0.044-0.144-0.067c-0.333-0.16-0.64-0.325-0.935-0.491c-0.073-0.042-0.15-0.083-0.221-0.125 c-0.256-0.15-0.49-0.304-0.714-0.458c-0.089-0.062-0.18-0.123-0.263-0.185c-0.188-0.139-0.358-0.28-0.52-0.421 c-0.092-0.08-0.183-0.16-0.266-0.241c-0.132-0.128-0.249-0.257-0.358-0.386c-0.08-0.094-0.156-0.188-0.224-0.283 c-0.087-0.121-0.161-0.242-0.227-0.363c-0.055-0.101-0.104-0.202-0.146-0.303c-0.048-0.119-0.086-0.238-0.114-0.356 c-0.039-0.163-0.08-0.327-0.08-0.488v-8.207c0.12,0.109,0.257,0.216,0.387,0.324C5.325,13.677,5.392,13.737,5.467,13.797z M27.866,2c13.554,0,23,3.952,23,7.5s-9.446,7.5-23,7.5s-23-3.952-23-7.5S14.312,2,27.866,2z"/></g></svg>'
                )
                ->section('Content')
                ->route('eloquenty.collections.index')
                ->can('index', [\Statamic\Contracts\Entries\Collection::class])
                ->children($children);
        });
    }

    private function registerServiceProviders(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
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

        $this->app->bind('eloquenty_entry', function () {
            return app(EntryRepository::class);
        });
        $this->app->alias('EloquentyEntry', EloquentyEntry::class);
    }

    protected function registerFieldtypes()
    {
        EloquentyEntries::register();
    }
}
