<?php

namespace Eloquenty\Entries;

use Eloquenty\Facades\EloquentyEntry as EntryFacade;
use Statamic\Entries\Collection as StatamicCollection;

class Collection extends StatamicCollection
{
    // Eloquenty: Added for retrieving the Eloquenty QueryBuilder
    public function queryEloquentyEntries()
    {
        return EntryFacade::query()->where('collection', $this->handle());
    }
}
