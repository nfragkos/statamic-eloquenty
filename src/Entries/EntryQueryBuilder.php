<?php

namespace Eloquenty\Entries;

use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Entries\EntryCollection;
use Statamic\Query\EloquentQueryBuilder;

/**
 * Eloquenty: From Statamic Eloquent Driver with modifications.
 */
class EntryQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    use QueriesTaxonomizedEntries;

    //Eloquenty: Use Eloquenty Entry for transform
    protected function transform($items, $columns = [])
    {
        return EntryCollection::make($items)->map(function ($model) {
            return Entry::fromModel($model);
        });
    }

    protected function column($column)
    {
        if ($column == 'origin') {
            $column = 'origin_id';
        }

        $columns = [
            'id',
            'site',
            'origin_id',
            'published',
            'status',
            'slug',
            'uri',
            'date',
            'collection',
            'created_at',
            'updated_at',
        ];

        if (!in_array($column, $columns)) {
            $column = 'data->' . $column;
        }

        return $column;
    }

    public function get($columns = ['*'])
    {
        $this->addTaxonomyWheres();

        return parent::get($columns);
    }

    public function paginate($perPage = null, $columns = ['*'])
    {
        $this->addTaxonomyWheres();

        return parent::paginate($perPage, $columns);
    }

    public function count()
    {
        $this->addTaxonomyWheres();

        return parent::count();
    }
}
