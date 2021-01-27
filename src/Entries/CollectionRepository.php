<?php

namespace Nfragkos\Eloquenty\Entries;

use Nfragkos\Eloquenty\Models\Entry as EntryModel;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Stache\Repositories\CollectionRepository as StatamicCollectionRepository;

/**
 * Eloquenty: From Statamic Eloquent Driver
 */
class CollectionRepository extends StatamicCollectionRepository
{
    public function updateEntryUris($collection, $ids = null)
    {
        $query = $collection->queryEntries();

        if ($ids) {
            $query->whereIn('id', $ids);
        }

        $query->get()->each(function ($entry) {
            EntryModel::where('id', $entry->id())->update(['uri' => $entry->uri()]);
        });
    }

    /*
     * Eloquenty: Replace original collection class so that $collection->queryEntries()
     * will return the correct query builder for both eloquenty and original collections.
     */
    public static function bindings(): array
    {
        return [
            CollectionContract::class => Collection::class,
        ];
    }
}
