<?php

namespace Eloquenty\Tags;

use Eloquenty\Entries\EntryRepository;
use Statamic\Tags\Collection\Entries as StatamicEntries;

class Entries extends StatamicEntries
{
    protected function query()
    {
        $query = app(EntryRepository::class)->query()
            ->whereIn('collection', $this->collections->map->handle()->all());

        $this->querySite($query);
        $this->queryStatus($query);
        $this->queryPastFuture($query);
        $this->querySinceUntil($query);
        $this->queryTaxonomies($query);
        $this->queryRedirects($query);
        $this->queryConditions($query);
        $this->queryScopes($query);
        $this->queryOrderBys($query);

        return $query;
    }
}
