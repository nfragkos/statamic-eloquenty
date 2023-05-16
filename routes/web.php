<?php

use Illuminate\Support\Facades\Route;
use Eloquenty\Facades\Eloquenty;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Stringy\StaticStringy;

/**
 * Web Routes
 * Add routes for Eloquenty collections
 */

foreach (Eloquenty::collections() as $handle) {
    foreach (Collection::find($handle)->routes() as $site => $route) {
        if (!$route) {
            continue;
        }

        $siteUrl = StaticStringy::ensureLeft(Site::get($site)->url(), '/');
        $route = StaticStringy::ensureLeft($route, '/');
        $uri = StaticStringy::ensureLeft($route, $siteUrl);

        Route::get($uri, 'Eloquenty\Http\Controllers\FrontendController@index');
    }
}
