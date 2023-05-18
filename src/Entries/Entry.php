<?php

namespace Eloquenty\Entries;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Statamic\Contracts\Data\Augmented;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Entries\AugmentedEntry;
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

    public static function fromModel(Model $model)
    {
        $entry = (new static)
            ->origin($model->origin_id)
            ->locale($model->site)
            ->slug($model->slug)
            ->collection($model->collection)
            ->data($model->data)
            ->blueprint($model->data['blueprint'] ?? null)
            ->published($model->published)
            ->model($model);

        if ($model->date && $entry->collection()->dated()) {
            $entry->date($model->date);
        }

        if (config('statamic.system.track_last_update')) {
            $entry->set('updated_at', $model->updated_at ?? $model->created_at);
        }

        return $entry;
    }

    public function toModel()
    {
        $data = $this->data();

        if ($this->blueprint && $this->collection()->entryBlueprints()->count() > 1) {
            $data['blueprint'] = $this->blueprint;
        }

        $attributes = [
            'origin_id' => $this->origin()?->id(),
            'site' => $this->locale(),
            'slug' => $this->slug(),
            'uri' => $this->uri(),
            'date' => $this->hasDate() ? $this->date() : null,
            'collection' => $this->collectionHandle(),
            'data' => $data->except(EntryQueryBuilder::COLUMNS),
            'published' => $this->published(),
            'status' => $this->status(),
            'updated_at' => $this->lastModified(),
            //'order'      => $this->order(), // Eloquenty: we dont have this yet
        ];

        if ($id = $this->id()) {
            $attributes['id'] = $id;
        }

        //Eloquenty: Use eloquenty entry model
        return EntryModel::findOrNew($this->id())->fill($attributes);
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

    public function fileLastModified()
    {
        return $this->model?->updated_at ?? Carbon::now();
    }

    public function lastModified()
    {
        return $this->fileLastModified();
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
            if (!$this->origin instanceof EntryContract) {
                if ($model = EntryModel::find($this->origin)) {
                    $this->origin = self::fromModel($model);
                }
            }

            return $this->origin;
        }

        if (!$this->model?->origin_id) {
            return;
        }

        if ($model = EntryModel::find($this->model->origin_id)) {
            $this->origin = self::fromModel($model);
        }

        return $this->origin ?? null;
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

    public function newAugmentedInstance(): Augmented
    {
        // Eloquenty: ensure taxonomies are augmented
        $this->taxonomize();

        return new AugmentedEntry($this);
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

    // Eloquenty: Use Eloquenty repository
    public function fresh()
    {
        return app(EntryRepository::class)->find($this->id);
    }
}
