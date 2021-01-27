<?php

namespace Nfragkos\Eloquenty\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Arr;
use Statamic\Support\Str;

/**
 * Class Entry
 * @package Nfragkos\Eloquenty\Models
 * @mixin \Eloquent
 */
class Entry extends Eloquent
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
            if ($entry->{$entry->getKeyName()} === null) {
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
