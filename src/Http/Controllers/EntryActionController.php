<?php

namespace Nfragkos\Eloquenty\Http\Controllers;

use Nfragkos\Eloquenty\Entries\DbEntryRepository;
use Statamic\Http\Controllers\CP\Collections\EntryActionController as StatamicEntryActionController;

class EntryActionController extends StatamicEntryActionController
{
    protected function getSelectedItems($items, $context)
    {
        return $items->map(function ($item) {
            // Eloquenty: Use DbEntry repository
            return app(DbEntryRepository::class)->find($item);
        });
    }
}
