<?php

namespace Eloquenty\Entries;

use Statamic\Entries\Collection as StatamicCollection;

class Collection extends StatamicCollection
{
    // Eloquenty: Added for retrieving the Eloquenty QueryBuilder
    public function queryEloquentyEntries()
    {
        return app(EntryRepository::class)->query()->where('collection', $this->handle());
    }
}
