<?php

namespace Eloquenty\Entries;

use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Stache\Repositories\CollectionRepository as StatamicCollectionRepository;

/**
 * Eloquenty: From Statamic Eloquent Driver
 */
class CollectionRepository extends StatamicCollectionRepository
{
    public function updateEntryUris($collection, $ids = null)
    {
        $query = $collection->queryEloquentyEntries();

        if ($ids) {
            $query->whereIn('id', $ids);
        }

        $query->get()->each(function ($entry) {
            EntryModel::where('id', $entry->id())->update(['uri' => $entry->uri()]);
        });
    }

    /*
     * Eloquenty: Add queryEloquentyEntries() method that returns the correct query builder for Eloquenty collections
     */
    public static function bindings(): array
    {
        return [
            CollectionContract::class => Collection::class,
        ];
    }
}
