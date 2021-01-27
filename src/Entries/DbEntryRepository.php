<?php

namespace Nfragkos\Eloquenty\Entries;

use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Stache\Repositories\EntryRepository as StacheRepository;

/**
 * Eloquenty: From Statamic Eloquent Driver with modifications.
 */
class DbEntryRepository extends StacheRepository
{
    // Eloquenty: Bind DbEntry and DbEntryQueryBuilder
    public static function bindings(): array
    {
        return [
            EntryContract::class => DbEntry::class,
            QueryBuilder::class => DbEntryQueryBuilder::class,
        ];
    }

    // Eloquenty: Use bEntryQueryBuilder
    public function query()
    {
        return app(DbEntryQueryBuilder::class);
    }

    public function save($entry)
    {
        $model = $entry->toModel();

        // Save entry
        $model->save();

        $entry->model($model->fresh());
    }

    public function delete($entry)
    {
        $entry->model()->delete();
    }
}
