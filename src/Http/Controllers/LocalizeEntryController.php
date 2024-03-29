<?php

namespace Eloquenty\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\Collections\LocalizeEntryController as StatamicLocalizeEntryController;

class LocalizeEntryController extends StatamicLocalizeEntryController
{
    public function __invoke(Request $request, $collection, $entry)
    {
        $request->validate(['site' => 'required']);

        $localized = $entry->makeLocalization($site = $request->site);

        // Eloquenty: Structures are disabled for eloquenty collections
        //$this->addToStructure($collection, $entry, $localized);

        $localized->store(['user' => User::fromUser($request->user())]);

        return [
            'handle' => $site,
            'url' => $localized->editUrl(),
        ];
    }
}
