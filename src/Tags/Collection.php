<?php

namespace Nfragkos\Eloquenty\Tags;

use Nfragkos\Eloquenty\Entries\DbEntryRepository;
use Statamic\Tags\Collection\Collection as StatamicCollectionTag;

class Collection extends StatamicCollectionTag
{
    protected static $handle = 'eloquenty_collection';
    protected static $aliases = ['eloquenty'];

    protected function entries()
    {
        return new Entries($this->params);
    }

    protected function currentEntry()
    {
        return app(DbEntryRepository::class)->find($this->params->get('current', $this->context->get('id')));
    }
}
