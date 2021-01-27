<?php

namespace Nfragkos\Eloquenty\Entries;

use Nfragkos\Eloquenty\Facades\Eloquenty;
use Statamic\Entries\Collection as StatamicCollection;

class Collection extends StatamicCollection
{
    public function queryEntries()
    {
        // Eloquenty: If collection is handled by eloquenty then return DbEntry query builder
        if (Eloquenty::isEloquentyCollection($this->handle())) {
            return app(DbEntryRepository::class)->query()->where('collection', $this->handle());
        }

        // Eloquenty: Return the original collection query builder
        return parent::queryEntries();
    }
}
