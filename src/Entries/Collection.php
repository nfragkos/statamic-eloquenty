<?php

namespace Eloquenty\Entries;

use Eloquenty\Facades\Eloquenty;
use Eloquenty\Facades\EloquentyEntry as EntryFacade;
use Statamic\Entries\Collection as StatamicCollection;

class Collection extends StatamicCollection
{
    // Eloquenty: Added for retrieving the Eloquenty QueryBuilder
    public function queryEntries()
    {
        if (Eloquenty::isEloquentyCollection($this->handle)) {
            return EntryFacade::query()->where('collection', $this->handle());
        }

        return parent::queryEntries();
    }

    /***
     * Eloquenty: Added for retrieving the Eloquenty QueryBuilder
     * @deprecated v2.0.0-beta.3 Use queryEntries() instead.
     */
    public function queryEloquentyEntries()
    {
        return EntryFacade::query()->where('collection', $this->handle());
    }
}
