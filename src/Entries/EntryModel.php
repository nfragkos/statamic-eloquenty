<?php

namespace Eloquenty\Entries;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Arr;
use Statamic\Support\Str;

/**
 * Class Entry
 * @package Eloquenty\Entries
 * @mixin \Eloquent
 */
class EntryModel extends Eloquent
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $table = 'eloquenty_entries';

    protected $casts = [
        'date' => 'datetime',
        'data' => 'json',
        'published' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($entry) {
            if (empty($entry->{$entry->getKeyName()})) {
                $entry->{$entry->getKeyName()} = (string)Str::uuid();
            }
        });
    }

    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'data->author');
    }

    public function origin()
    {
        return $this->belongsTo(static::class);
    }

    public function parent()
    {
        return $this->belongsTo(static::class, 'data->parent');
    }

    public function getAttribute($key)
    {
        // Because the import script was importing `updated_at` into the
        // json data column, we will explicitly reference other SQL
        // columns first to prevent errors with that bad data.
        if (in_array($key, EntryQueryBuilder::COLUMNS)) {
            return parent::getAttribute($key);
        }

        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }
}
