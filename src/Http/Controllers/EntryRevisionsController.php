<?php

namespace Nfragkos\Eloquenty\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\Collections\EntryRevisionsController as StatamicEntryRevisionsController;

class EntryRevisionsController extends StatamicEntryRevisionsController
{
    public function index(Request $request, $collection, $entry)
    {
        $revisions = $entry
            ->revisions()
            ->reverse()
            ->prepend($this->workingCopy($entry))
            ->filter();

        // The first non manually created revision would be considered the "current"
        // version. It's what corresponds to what's in the content directory.
        optional($revisions->first(function ($revision) {
            return $revision->action() != 'revision';
        }))->attribute('current', true);

        return $revisions
            ->groupBy(function ($revision) {
                // Eloquenty: Fix integer date clone error
                if (is_int($revision->date())) {
                    $revision->date(Carbon::createFromTimestamp($revision->date()));
                }

                return $revision->date()->clone()->startOfDay()->format('U');
            })->map(function ($revisions, $day) {
                return compact('day', 'revisions');
            })->reverse()->values();
    }
}
