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
        'published' => 'bool',
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

    public function origin()
    {
        return $this->belongsTo(self::class);
    }

    public function getAttribute($key)
    {
        return Arr::get($this->getAttributeValue('data'), $key, parent::getAttribute($key));
    }
}
