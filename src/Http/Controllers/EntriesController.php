<?php

namespace Eloquenty\Http\Controllers;

use Eloquenty\Entries\Entry;
use Eloquenty\Entries\EntryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\CP\Breadcrumbs;
use Statamic\Exceptions\BlueprintNotFoundException;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\Collections\EntriesController as StatamicEntriesController;
use Statamic\Http\Resources\CP\Entries\Entry as EntryResource;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class EntriesController extends StatamicEntriesController
{
    use QueriesFilters;

    protected function indexQuery($collection)
    {
        // Eloquenty: Get the correct query builder
        $query = $collection->queryEloquentyEntries();

        if ($search = request('search')) {
            if ($collection->hasSearchIndex()) {
                return $collection->searchIndex()->ensureExists()->search($search);
            }

            $query->where('title', 'like', '%' . $search . '%');
        }

        return $query;
    }

    public function create(Request $request, $collection, $site)
    {
        $this->authorize('create', [EntryContract::class, $collection]);

        $blueprint = $collection->entryBlueprint($request->blueprint);

        if (!$blueprint) {
            throw new \Exception(__('A valid blueprint is required.'));
        }

        if (User::current()->cant('edit-other-authors-entries', [EntryContract::class, $collection, $blueprint])) {
            $blueprint->ensureFieldHasConfig('author', ['visibility' => 'read_only']);
        }

        $values = \Statamic\Facades\Entry::make()->collection($collection)->values()->all();

        // Eloquenty: Structures are disabled for eloquenty collections
        //if ($collection->hasStructure() && $request->parent) {
        //    $values['parent'] = $request->parent;
        //}

        $fields = $blueprint
            ->fields()
            ->addValues($values)
            ->preProcess();

        $values = collect([
            'title' => null,
            'slug' => null,
            'published' => $collection->defaultPublishState(),
        ])->merge($fields->values());

        $viewData = [
            'title' => $collection->createLabel(),
            'actions' => [
                // Eloquenty: Use eloquenty route for store
                'save' => cp_route('eloquenty.collections.entries.store', [$collection->handle(), $site->handle()]),
            ],
            'values' => $values->all(),
            'meta' => $fields->meta(),
            'collection' => $collection->handle(),
            'collectionCreateLabel' => $collection->createLabel(),
            'collectionHasRoutes' => !is_null($collection->route($site->handle())),
            'blueprint' => $blueprint->toPublishArray(),
            'published' => $collection->defaultPublishState(),
            'locale' => $site->handle(),
            'localizations' => $this->getAuthorizedSitesForCollection($collection)->map(function ($handle) use ($collection, $site, $blueprint) {
                    return [
                        'handle' => $handle,
                        'name' => Site::get($handle)->name(),
                        'active' => $handle === $site->handle(),
                        'exists' => false,
                        'published' => false,
                    'url' => cp_route('eloquenty.collections.entries.create', [$collection->handle(), $handle, 'blueprint' => $blueprint->handle()]),
                    'livePreviewUrl' => $collection->route($handle) ? cp_route('eloquenty.collections.entries.preview.create', [$collection->handle(), $handle]) : null, // Eloquenty: Use eloquenty route for preview
                    ];
            })->values()->all(),
            'revisionsEnabled' => $collection->revisionsEnabled(),
            'breadcrumbs' => $this->breadcrumbs($collection),
            'canManagePublishState' => User::current()->can('publish ' . $collection->handle() . ' entries'),
            'previewTargets' => $collection->previewTargets()->all(),
            'autosaveInterval' => $collection->autosaveInterval(),
        ];

        if ($request->wantsJson()) {
            return collect($viewData);
        }

        // Eloquenty: Use eloquenty view
        return view('eloquenty::entries.create', $viewData);
    }

    public function store(Request $request, $collection, $site)
    {
        $this->authorize('store', [EntryContract::class, $collection]);

        $blueprint = $collection->entryBlueprint($request->_blueprint);

        $data = $request->all();

        if (User::current()->cant('edit-other-authors-entries', [EntryContract::class, $collection, $blueprint])) {
            $data['author'] = [User::current()->id()];
        }

        $fields = $blueprint
            ->ensureField('published', ['type' => 'toggle'])
            ->fields()
            ->addValues($data);

        $fields
            ->validator()
            ->withRules(\Statamic\Facades\Entry::createRules($collection, $site))
            ->withReplacements([
                'collection' => $collection->handle(),
                'site' => $site->handle(),
            ])->validate();

        $values = $fields->process()->values()->except(['slug', 'blueprint', 'published']);

        // Eloquenty: Create Eloquenty Entry
        $entry = app(Entry::class)
            ->collection($collection)
            ->blueprint($request->_blueprint)
            ->locale($site->handle())
            ->published($request->get('published'))
            ->slug($this->resolveSlug($request));

        if ($collection->dated()) {
            $entry->date($blueprint->field('date')->fieldtype()->augment($values->pull('date')));
        }

        $entry->data($values);

        // Eloquenty: Structures are disabled for eloquenty collections
        //if ($structure = $collection->structure()) {
        //    $tree = $structure->in($site->handle());
        //}
        //if ($structure && ! $collection->orderable()) {
        //    $parent = $values['parent'] ?? null;
        //    $entry->afterSave(function ($entry) use ($parent, $tree) {
        //        if ($parent && optional($tree->find($parent))->isRoot()) {
        //            $parent = null;
        //        }
        //
        //        $tree->appendTo($parent, $entry)->save();
        //    });
        //}

        $this->validateUniqueUri($entry, $tree ?? null, $parent ?? null);

        if ($entry->revisionsEnabled()) {
            $saved = $entry->store([
                'message' => $request->message,
                'user' => User::current(),
            ]);
        } else {
            $saved = $entry->updateLastModified(User::current())->save();
        }

        return (new EntryResource($entry))->additional(['saved' => $saved]);
    }

    public function edit(Request $request, $collection, $entry)
    {
        $this->authorize('view', $entry);

        $entry = $entry->fromWorkingCopy();

        $blueprint = $entry->blueprint();

        if (!$blueprint) {
            throw new BlueprintNotFoundException($entry->value('blueprint'), 'collections/' . $collection->handle());
        }

        $blueprint->setParent($entry);

        if (User::current()->cant('edit-other-authors-entries', [EntryContract::class, $collection, $blueprint])) {
            $blueprint->ensureFieldHasConfig('author', ['visibility' => 'read_only']);
        }

        [$values, $meta] = $this->extractFromFields($entry, $blueprint);

        if ($hasOrigin = $entry->hasOrigin()) {
            [$originValues, $originMeta] = $this->extractFromFields($entry->origin(), $blueprint);
        }

        $viewData = [
            'title' => $entry->value('title'),
            'reference' => $entry->reference(),
            'editing' => true,
            'actions' => [
                'save' => $entry->updateUrl(),
                'publish' => $entry->publishUrl(),
                'unpublish' => $entry->unpublishUrl(),
                'revisions' => $entry->revisionsUrl(),
                'restore' => $entry->restoreRevisionUrl(),
                'createRevision' => $entry->createRevisionUrl(),
                'editBlueprint' => cp_route('collections.blueprints.edit', [$collection, $blueprint]),
            ],
            'values' => array_merge($values, ['id' => $entry->id()]),
            'meta' => $meta,
            'collection' => $collection->handle(),
            'collectionHasRoutes' => !is_null($collection->route($entry->locale())),
            'blueprint' => $blueprint->toPublishArray(),
            'readOnly' => User::current()->cant('edit', $entry),
            'locale' => $entry->locale(),
            'localizedFields' => $entry->data()->keys()->all(),
            'originBehavior' => $collection->originBehavior(),
            'isRoot' => $entry->isRoot(),
            'hasOrigin' => $hasOrigin,
            'originValues' => $originValues ?? null,
            'originMeta' => $originMeta ?? null,
            'permalink' => $entry->absoluteUrl(),
            'localizations' => $this->getAuthorizedSitesForCollection($collection)->map(function ($handle) use ($entry) {
                $localized = $entry->in($handle);
                $exists = $localized !== null;

                return [
                    'handle' => $handle,
                    'name' => Site::get($handle)->name(),
                    'active' => $handle === $entry->locale(),
                    'exists' => $exists,
                    'root' => $exists ? $localized->isRoot() : false,
                    'origin' => $exists ? $localized->id() === optional($entry->origin())->id() : null,
                    'published' => $exists ? $localized->published() : false,
                    'status' => $exists ? $localized->status() : null,
                    'url' => $exists ? $localized->editUrl() : null,
                    'livePreviewUrl' => $exists ? $localized->livePreviewUrl() : null,
                ];
            })->values()->all(),
            'hasWorkingCopy' => $entry->hasWorkingCopy(),
            'preloadedAssets' => $this->extractAssetsFromValues($values),
            'revisionsEnabled' => $entry->revisionsEnabled(),
            'breadcrumbs' => $this->breadcrumbs($collection),
            'canManagePublishState' => User::current()->can('publish', $entry),
            'previewTargets' => $collection->previewTargets()->all(),
            'autosaveInterval' => $collection->autosaveInterval(),
        ];

        if ($request->wantsJson()) {
            return collect($viewData);
        }

        if ($request->has('created')) {
            session()->now('success', __('Entry created'));
        }

        // Eloquenty: Use eloquenty view
        return view('eloquenty::entries.edit', array_merge($viewData, [
            'entry' => $entry,
        ]));
    }

    public function update(Request $request, $collection, $entry)
    {
        $this->authorize('update', $entry);

        $entry = $entry->fromWorkingCopy();

        $blueprint = $entry->blueprint();

        $data = $request->except('id');

        if (User::current()->cant('edit-other-authors-entries', [EntryContract::class, $collection, $blueprint])) {
            $data['author'] = Arr::wrap($entry->value('author'));
        }

        $fields = $entry
            ->blueprint()
            ->ensureField('published', ['type' => 'toggle'])
            ->fields()
            ->addValues($data);

        $fields
            ->validator()
            ->withRules(\Statamic\Facades\Entry::updateRules($collection, $entry))
            ->withReplacements([
                'id' => $entry->id(),
                'collection' => $collection->handle(),
                'site' => $entry->locale(),
            ])->validate();

        $values = $fields->process()->values();

        // Eloquenty: Structures are disabled for eloquenty collections
        //$parent = $values->pull('parent');

        if ($explicitBlueprint = $values->pull('blueprint')) {
            $entry->blueprint($explicitBlueprint);
        }

        $values = $values->except(['slug', 'published']);

        if ($entry->collection()->dated()) {
            $entry->date($entry->blueprint()->field('date')->fieldtype()->augment($values->pull('date')));
        }

        if ($entry->hasOrigin()) {
            $entry->data($values->only($request->input('_localized')));
        } else {
            $entry->merge($values);
        }

        $entry->slug($this->resolveSlug($request));

        // Eloquenty: Structures are disabled for eloquenty collections
        //if ($structure = $collection->structure()) {
        //$tree = $entry->structure()->in($entry->locale());
        //}
        //if ($structure && ! $collection->orderable()) {
        //    $this->validateParent($entry, $tree, $parent);
        //
        //    $entry->afterSave(function ($entry) use ($parent, $tree) {
        //        if ($parent && optional($tree->find($parent))->isRoot()) {
        //            $parent = null;
        //        }
        //
        //        $tree
        //            ->move($entry->id(), $parent)
        //            ->save();
        //    });
        //}

        $this->validateUniqueUri($entry, $tree ?? null, $parent ?? null);

        if ($entry->revisionsEnabled() && $entry->published()) {
            $saved = $entry
                ->makeWorkingCopy()
                ->user(User::current())
                ->save();

            // catch any changes through RevisionSaving event
            $entry = $entry->fromWorkingCopy();
        } else {
            if (!$entry->revisionsEnabled() && User::current()->can('publish', $entry)) {
                $entry->published($request->published);
            }

            $saved = $entry->updateLastModified(User::current())->save();
        }

        [$values] = $this->extractFromFields($entry, $blueprint);

        return (new EntryResource($entry->fresh()))->additional([
            'saved' => $saved,
            'data' => [
                'values' => $values,
            ],
        ]);
    }

    private function resolveSlug($request)
    {
        return function ($entry) use ($request) {
            if ($request->slug) {
                return $request->slug;
            }

            if ($entry->blueprint()->hasField('slug')) {
                return Str::slug($request->title ?? $entry->autoGeneratedTitle(), '-', $entry->site()->lang());
            }

            return null;
        };
    }

    private function validateUniqueUri($entry, $tree, $parent)
    {
        //if (!$uri = $this->entryUri($entry, $tree, $parent)) {
        //    return;
        //}

        // Eloquenty: Use Eloquenty repository, check slug and site to be unique
        $existing = app(EntryRepository::class)->query()
            ->where('slug', $entry->slug())
            ->where('site', $entry->locale())
            ->first();

        if (!$existing || $existing->id() === $entry->id()) {
            return;
        }

        throw ValidationException::withMessages(['slug' => __('statamic::validation.unique_uri')]);
    }

    protected function breadcrumbs($collection)
    {
        // Eloquenty: Use eloquenty routes for breadcrumbs
        return new Breadcrumbs([
            [
                'text' => 'Eloquenty Collections',
                'url' => cp_route('eloquenty.collections.index'),
            ],
            [
                'text' => $collection->title(),
                'url' => cp_route('eloquenty.collections.show', [$collection]),
            ],
        ]);
    }
}
