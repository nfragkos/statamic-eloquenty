<?php

namespace Eloquenty\Entries;

use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Stache\Repositories\EntryRepository as StacheRepository;

/**
 * Eloquenty: From Statamic Eloquent Driver with modifications.
 */
class EntryRepository extends StacheRepository
{
    // Eloquenty: Bind Entry and EntryQueryBuilder
    public static function bindings(): array
    {
        return [
            EntryContract::class => Entry::class,
            QueryBuilder::class => EntryQueryBuilder::class,
        ];
    }

    // Eloquenty: Use bEntryQueryBuilder
    public function query()
    {
        return app(EntryQueryBuilder::class);
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
