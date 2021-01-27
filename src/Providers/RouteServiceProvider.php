<?php

namespace Nfragkos\Eloquenty\Providers;

use Illuminate\Support\Facades\Route;
use Nfragkos\Eloquenty\Entries\DbEntryRepository;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Providers\RouteServiceProvider as StatamicServiceProvider;

class RouteServiceProvider extends StatamicServiceProvider
{
    protected function bindEntries()
    {
        // On Eloquenty routes we bind entry with DbEntry
        if (starts_with(request()->path(), 'cp/eloquenty')) {
            Route::bind('entry', function ($handle, $route) {
                $dbEntry = $this->app[DbEntryRepository::class];

                throw_if(
                    !($entry = $dbEntry->find($handle)) || $entry->collection()->id() !== $route->parameter('collection')->id(),
                    new NotFoundHttpException("DbEntry [$handle] not found.")
                );

                return $entry;
            });

            return;
        }

        parent::bindEntries();
    }
}
