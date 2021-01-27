<?php

namespace Nfragkos\Eloquenty\Commands;

use DB;
use Illuminate\Console\Command;
use Nfragkos\Eloquenty\Models\Entry as EntryModel;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Entry;

class ImportEntries extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eloquenty:import-entries  {collection : The handle of the collection to import entries}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file based entries of a specific collection into the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->importEntries();

        return 0;
    }

    private function importEntries()
    {
        $collection = $this->argument('collection');
        $entries = Entry::whereCollection($collection);
        $bar = $this->output->createProgressBar($entries->count());

        try {
            DB::beginTransaction();

            $entries->each(function ($entry) use ($bar) {
                $model = $this->toModel($entry);

                // Save entry
                $model->save();

                $bar->advance();
            });

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();
            throw $e;
        }

        $bar->finish();
        $this->line('');
        $this->info('Entries imported');
    }

    private function toModel($entry)
    {
        return new EntryModel([
            'id' => $entry->id(),
            'origin_id' => optional($entry->origin())->id(),
            'site' => $entry->locale(),
            'slug' => $entry->slug(),
            'uri' => $entry->uri(),
            'date' => $entry->hasDate() ? $entry->date() : null,
            'collection' => $entry->collectionHandle(),
            'data' => $entry->data(),
            'published' => $entry->published(),
            'status' => $entry->status(),
        ]);
    }
}
