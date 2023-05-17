<?php

namespace Eloquenty\Entries;

use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Facades\Blink;
use Statamic\Stache\Repositories\EntryRepository as StacheRepository;

/**
 * Eloquenty: From Statamic Eloquent Driver with modifications.
 */
class EntryRepository extends StacheRepository
{
    // Eloquenty: Bind Entry and EntryQueryBuilder
    public static function bindings(): array
    {
        return [
            EntryContract::class => Entry::class,
            QueryBuilder::class => EntryQueryBuilder::class,
        ];
    }

    // Eloquenty: Use bEntryQueryBuilder
    public function query()
    {
        return app(EntryQueryBuilder::class);
    }

    public function find($id): ?EntryContract
    {
        $blinkKey = "eloquent-entry-{$id}";
        $item = Blink::once($blinkKey, function () use ($id) {
            return $this->query()->where('id', $id)->first();
        });

        if (!$item) {
            Blink::forget($blinkKey);

            return null;
        }

        return $this->substitutionsById[$item->id()] ?? $item;
    }

    public function findByUri(string $uri, string $site = null): ?EntryContract
    {
        $blinkKey = "eloquent-entry-{$uri}" . ($site ? '-' . $site : '');
        $item = Blink::once($blinkKey, function () use ($uri, $site) {
            return parent::findByUri($uri, $site);
        });

        if (!$item) {
            Blink::forget($blinkKey);

            return null;
        }

        return $this->substitutionsById[$item->id()] ?? $item;
    }

    public function save($entry)
    {
        $model = $entry->toModel();
        $model->save();

        $entry->model($model->fresh());

        Blink::put("eloquent-entry-{$entry->id()}", $entry);
        Blink::put("eloquent-entry-{$entry->uri()}", $entry);
    }

    public function delete($entry)
    {
        $entry->model()->delete();
    }
}
