<?php

namespace Eloquenty\Commands;

use Eloquenty\Facades\EloquentyEntry;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;

class TermAssociations extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eloquenty:terms-associate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronises Eloquenty entries term associations.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (EloquentyEntry::all() as $entry) {
            $entry->taxonomize();
        }

        return 0;
    }
}
