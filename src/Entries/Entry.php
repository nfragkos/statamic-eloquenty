<?php

namespace Eloquenty\Entries;

use Exception;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Statamic\Entries\Entry as FileEntry;
use Statamic\Events\EntryCreated;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;

/**
 * Eloquenty: From Statamic Eloquent Driver with modifications.
 */
class Entry extends FileEntry
{
    protected $model;

    public static function fromModel(Eloquent $model)
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
        $data = $this->data();

        if ($this->blueprint && $this->collection()->entryBlueprints()->count() > 1) {
            $data['blueprint'] = $this->blueprint;
        }

        //Eloquenty: Use eloquenty entry model
        return EntryModel:: findOrNew($this->id())->fill([
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

        // Eloquenty: Fix error when model is null
        if (!isset($this->model) || !$this->model->origin) {
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
            throw new Exception('Cannot delete an entry with localizations.');
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

        app(EntryRepository::class)->delete($this);

        //EntryDeleted::dispatch($this);

        return true;
    }

    // Eloquenty: Fix detach entry localizations
    public function detachLocalizations()
    {
        app(EntryRepository::class)->query()
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
        //$isNew = is_null(Facades\Entry::find($this->id()));
        $isNew = is_null(app(EntryRepository::class)->find($this->id()));

        $afterSaveCallbacks = $this->afterSaveCallbacks;
        $this->afterSaveCallbacks = [];
        if ($this->withEvents) {
            if (EntrySaving::dispatch($this) === false) {
                return false;
            }
        }

        if ($this->collection()->autoGeneratesTitles()) {
            $this->set('title', $this->autoGeneratedTitle());
        }

        $this->slug($this->slug());

        //Facades\Entry::save($this);
        app(EntryRepository::class)->save($this);

        //if ($this->id()) {
        //    Blink::store('structure-page-entries')->forget($this->id());
        //    Blink::store('structure-uris')->forget($this->id());
        //    Blink::store('structure-entries')->flush();
        //}
        //
        //$this->taxonomize();

        optional(Collection::findByMount($this))->updateEntryUris();

        foreach ($afterSaveCallbacks as $callback) {
            $callback($this);
        }

        if ($this->withEvents) {
            if ($isNew) {
                EntryCreated::dispatch($this);
            }

            EntrySaved::dispatch($this);
        }

        return true;
    }

    // Eloquenty: Use Eloquenty Entry when making localization
    public function makeLocalization($site)
    {
        return app(static::class)
            ->collection($this->collection)
            ->origin($this)
            ->locale($site)
            ->slug($this->slug())
            ->date($this->date());
    }

    // Eloquenty: Use Eloquenty EntryRepository for finding entry descendants
    public function descendants()
    {
        if (!$this->localizations) {
            $this->localizations = app(EntryRepository::class)->query()
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
