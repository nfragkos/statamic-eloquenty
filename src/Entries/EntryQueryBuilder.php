<?php

namespace Eloquenty\Entries;

use Illuminate\Support\Str;
use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Entries\EntryCollection;
use Statamic\Query\EloquentQueryBuilder;

/**
 * Eloquenty: From Statamic Eloquent Driver with modifications.
 */
class EntryQueryBuilder extends EloquentQueryBuilder implements QueryBuilder
{
    use QueriesTaxonomizedEntries;

    const COLUMNS = [
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

    //Eloquenty: Use Eloquenty Entry for transform
    protected function transform($items, $columns = [])
    {
        return EntryCollection::make($items)->map(function ($model) use ($columns) {
            return Entry::fromModel($model)->selectedQueryColumns($columns);
        });
    }

    protected function column($column)
    {
        if (!is_string($column)) {
            return $column;
        }

        $table = Str::contains($column, '.') ? Str::before($column, '.') : '';
        $column = Str::after($column, '.');

        if ($column == 'origin') {
            $column = 'origin_id';
        }

        if (!in_array($column, self::COLUMNS)) {
            if (!Str::startsWith($column, 'data->')) {
                $column = 'data->' . $column;
            }
        }

        return ($table ? $table . '.' : '') . $column;
    }

    public function find($id, $columns = ['*'])
    {
        $model = parent::find($id, $columns);

        if ($model) {
            return Entry::fromModel($model)
                ->selectedQueryColumns($columns);
        }
    }

    public function get($columns = ['*'])
    {
        $this->addTaxonomyWheres();

        return parent::get($columns);
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->addTaxonomyWheres();

        return parent::paginate($perPage, $columns, $pageName, $page);
    }

    public function count()
    {
        $this->addTaxonomyWheres();

        return parent::count();
    }

    public function with($relations, $callback = null)
    {
        return $this;
    }
}
