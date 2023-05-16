<?php

namespace Eloquenty\Listeners;

use Eloquenty\Facades\Eloquenty;
use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\Events\AssetDeleted;
use Statamic\Events\AssetReferencesUpdated;
use Statamic\Events\AssetReplaced;
use Statamic\Events\AssetSaved;
use Statamic\Events\Subscriber;

class UpdateAssetReferences extends Subscriber implements ShouldQueue
{
    protected $listeners = [
        AssetSaved::class => 'handleSaved',
        AssetReplaced::class => 'handleReplaced',
        AssetDeleted::class => 'handleDeleted',
    ];

    public function subscribe($events)
    {
        if (config('eloquenty.update_references') === false) {
            return;
        }

        parent::subscribe($events);
    }

    protected function replaceReferences($asset, $originalPath, $newPath)
    {
        if (!$originalPath || $originalPath === $newPath) {
            return;
        }

        $offset = 0;
        $perPage = 300;
        $count = Eloquenty::repository()->query()->count();

        while ($offset < $count - 1) {
            $results = Eloquenty::repository()->query()->offset($offset)->limit($perPage)->get();

            foreach ($results as $result) {
                $entryData = $result->data()->toArray();

                foreach ($entryData as $key => $value) {
                    if ($value === $originalPath) {
                        $result->set($key, $newPath);
                        $result->saveQuietly();

                        AssetReferencesUpdated::dispatch($asset);
                    }
                }
            }

            $offset += $perPage;
        }
    }

    public function handleSaved(AssetSaved $event)
    {
        $asset = $event->asset;
        $originalPath = $asset->getOriginal('path');
        $newPath = $asset->path();

        $this->replaceReferences($asset, $originalPath, $newPath);
    }

    public function handleReplaced(AssetReplaced $event)
    {
        $asset = $event->newAsset;
        $originalPath = $event->originalAsset->path();
        $newPath = $event->newAsset->path();

        $this->replaceReferences($asset, $originalPath, $newPath);
    }

    public function handleDeleted(AssetDeleted $event)
    {
        $asset = $event->asset;
        $originalPath = $asset->getOriginal('path');
        $newPath = null;

        $this->replaceReferences($asset, $originalPath, $newPath);
    }
}
