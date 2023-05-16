<?php

namespace Eloquenty\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $subscribe = [
        \Eloquenty\Listeners\UpdateAssetReferences::class,
    ];

    public function boot()
    {
        //
    }
}
