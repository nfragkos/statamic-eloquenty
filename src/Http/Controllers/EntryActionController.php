<?php

namespace Eloquenty\Http\Controllers;

use Eloquenty\Entries\EntryRepository;
use Statamic\Http\Controllers\CP\Collections\EntryActionController as StatamicEntryActionController;

class EntryActionController extends StatamicEntryActionController
{
    protected function getSelectedItems($items, $context)
    {
        return $items->map(function ($item) {
            // Eloquenty: Use Eloquenty EntryRepository
            return app(EntryRepository::class)->find($item);
        });
    }
}
