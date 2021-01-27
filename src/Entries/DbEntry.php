<?php

namespace Nfragkos\Eloquenty\Entries;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Statamic\Entries\Entry as FileEntry;

/**
 * Eloquenty: From Statamic Eloquent Driver with modifications.
 */
class DbEntry extends FileEntry
{
    protected $model;

    public static function fromModel(Eloquent $model): static
    {
        return (new static)
            ->locale($model->site)
            ->slug($model->slug)
            ->date($model->date)
            ->collection($model->collection)
            ->data($model->data)
            ->blueprint($model->data['blueprint'] ?? null)
            ->published($model->published)
            ->model($model);
    }

    public function toModel()
    {
        //Eloquenty: Use eloquenty entry model
        $class = app('eloquenty.entries.model');

        $data = $this->data();

        if ($this->blueprint && $this->collection() && $this->collection()->entryBlueprints()->count() > 1) {
            $data['blueprint'] = $this->blueprint;
        }

        return $class::findOrNew($this->id())->fill([
            'origin_id' => $this->originId(),
            'site' => $this->locale(),
            'slug' => $this->slug(),
            'uri' => $this->uri(),
            'date' => $this->hasDate() ? $this->date() : null,
            'collection' => $this->collectionHandle(),
            'data' => $data,
            'published' => $this->published(),
            'status' => $this->status(),
        ]);
    }

    public function model($model = null)
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        $this->id($model->id);

        return $this;
    }

    public function lastModified()
    {
        return $this->model->updated_at;
    }

    public function origin($origin = null)
    {
        if (func_num_args() > 0) {
            $this->origin = $origin;

            // Eloquenty: Fix when detaching descendants
            if ($this->model) {
                $this->model->origin_id = $origin ? $origin->id() : null;
            }

            return $this;
        }

        if ($this->origin) {
            return $this->origin;
        }

        if (!$this->model->origin) {
            return null;
        }

        return self::fromModel($this->model->origin);
    }

    public function originId()
    {
        return optional($this->origin)->id() ?? optional($this->model)->origin_id;
    }

    public function hasOrigin()
    {
        return $this->originId() !== null;
    }

    // Eloquenty: Fix delete entry
    public function delete()
    {
        if ($this->descendants()->map->fresh()->filter()->isNotEmpty()) {
            throw new \Exception('Cannot delete an entry with localizations.');
        }

        //if ($this->hasStructure()) {
        //    tap($this->structure(), function ($structure) {
        //        tap($structure->in($this->locale()), function ($tree) {
        //            // Ugly, but it's moving all the child pages to the parent. TODO: Tidy.
        //            $parent = $this->parent();
        //            if (optional($parent)->isRoot()) {
        //                $parent = null;
        //            }
        //            $this->page()->pages()->all()->each(function ($child) use ($tree, $parent) {
        //                $tree->move($child->id(), optional($parent)->id());
        //            });
        //            $tree->remove($this);
        //        });
        //    })->save();
        //}

        app(DbEntryRepository::class)->delete($this);

        //EntryDeleted::dispatch($this);

        return true;
    }

    // Eloquenty: Fix detach entry localizations
    public function detachLocalizations()
    {
        app(DbEntryRepository::class)->query()
            ->where('collection', $this->collectionHandle())
            ->where('origin', $this->id())
            ->get()
            ->each(function ($loc) {
                $loc
                    ->origin(null)
                    ->data($this->data()->merge($loc->data()))
                    ->save();
            });

        return true;
    }

    // Eloquenty: Use eloquenty route
    public function editUrl()
    {
        return $this->cpUrl('eloquenty.collections.entries.edit');
    }

    // Eloquenty: Use eloquenty route
    public function updateUrl()
    {
        return $this->cpUrl('eloquenty.collections.entries.update');
    }

    // Eloquenty: Use eloquenty route
    public function publishUrl()
    {
        return $this->cpUrl('eloquenty.collections.entries.published.store');
    }

    // Eloquenty: Use eloquenty route
    public function unpublishUrl()
    {
        return $this->cpUrl('eloquenty.collections.entries.published.destroy');
    }

    // Eloquenty: Use eloquenty route
    public function revisionsUrl()
    {
        return $this->cpUrl('eloquenty.collections.entries.revisions.index');
    }

    // Eloquenty: Use eloquenty route
    public function createRevisionUrl()
    {
        return $this->cpUrl('eloquenty.collections.entries.revisions.store');
    }

    // Eloquenty: Use eloquenty route
    public function restoreRevisionUrl()
    {
        return $this->cpUrl('eloquenty.collections.entries.restore-revision');
    }

    // Eloquenty: Use eloquenty route
    public function livePreviewUrl()
    {
        return $this->collection()->route($this->locale())
            ? $this->cpUrl('eloquenty.collections.entries.preview.edit')
            : null;
    }

    // Eloquenty: Fix save entry
    public function save()
    {
        //$afterSaveCallbacks = $this->afterSaveCallbacks;
        //$this->afterSaveCallbacks = [];
        //
        //if (EntrySaving::dispatch($this) === false) {
        //    return false;
        //}

        app(DbEntryRepository::class)->save($this);

        //if ($this->id()) {
        //    Blink::store('structure-page-entries')->forget($this->id());
        //    Blink::store('structure-uris')->forget($this->id());
        //    Blink::store('structure-entries')->flush();
        //}
        //
        //$this->taxonomize();
        //
        //optional(Collection::findByMount($this))->updateEntryUris();
        //
        //foreach ($afterSaveCallbacks as $callback) {
        //    $callback($this);
        //}
        //
        //EntrySaved::dispatch($this);

        return true;
    }

    // Eloquenty: Use DbEntry when making localization
    public function makeLocalization($site)
    {
        return app(static::class)
            ->collection($this->collection)
            ->origin($this)
            ->locale($site)
            ->slug($this->slug())
            ->date($this->date());
    }

    // Eloquenty: Use DbEntry repository for finding entry descendants
    public function descendants()
    {
        if (!$this->localizations) {
            $this->localizations = app(DbEntryRepository::class)->query()
                ->where('collection', $this->collectionHandle())
                ->where('origin', $this->id())->get()
                ->keyBy->locale();
        }

        $localizations = collect($this->localizations);

        foreach ($localizations as $loc) {
            $localizations = $localizations->merge($loc->descendants());
        }

        return $localizations;
    }

    // Eloquenty: Structures are disabled for eloquenty collections
    public function hasStructure()
    {
        // return $this->collection()->hasStructure();
        return false;
    }
}
