<?php

namespace Nfragkos\Eloquenty\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Eloquenty
 * @package Nfragkos\Eloquenty\Facades
 * @method static array collections
 * @method static bool isEloquentyCollection(string $handle)
 */
class Eloquenty extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'eloquenty';
    }
}
