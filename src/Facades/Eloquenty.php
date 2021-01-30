<?php

namespace Eloquenty\Facades;

use Illuminate\Support\Facades\Facade;
use Statamic\Contracts\Entries\EntryRepository as RepositoryContract;

/**
 * Class Eloquenty
 * @package Eloquenty\Facades
 * @method static array collections
 * @method static bool isEloquentyCollection(string $handle)
 * @method static RepositoryContract repository()
 */
class Eloquenty extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'eloquenty';
    }
}
