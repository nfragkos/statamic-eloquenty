<?php

namespace Eloquenty\Listeners;

use Eloquenty\Facades\Eloquenty;
use Statamic\Events\CollectionSaved;

class UpdateEntriesUri
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param CollectionSaved $event
     * @return void
     */
    public function handle(CollectionSaved $event)
    {
        /**
         * Update posts uri if route of the collection has changed
         */

        if (Eloquenty::isEloquentyCollection($event->collection->handle())) {
            $event->collection->updateEntryUris();
        }
    }
}
