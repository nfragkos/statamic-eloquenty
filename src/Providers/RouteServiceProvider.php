<?php

namespace Eloquenty\Providers;

use Illuminate\Support\Facades\Route;
use Eloquenty\Entries\EntryRepository;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Providers\RouteServiceProvider as StatamicServiceProvider;

class RouteServiceProvider extends StatamicServiceProvider
{
    protected function bindEntries()
    {
        // On Eloquenty routes we bind entry with Eloquenty Entry class
        if (starts_with(request()->path(), 'cp/eloquenty')) {
            Route::bind('entry', function ($handle, $route) {
                $eloquentyEntry = $this->app[EntryRepository::class];

                throw_if(
                    !($entry = $eloquentyEntry->find($handle)) || $entry->collection()->id() !== $route->parameter('collection')->id(),
                    new NotFoundHttpException("Eloquenty Entry [$handle] not found.")
                );

                return $entry;
            });

            return;
        }

        parent::bindEntries();
    }
}
