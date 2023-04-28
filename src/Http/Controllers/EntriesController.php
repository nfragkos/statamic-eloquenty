<?php

namespace Eloquenty\Http\Controllers;

use Eloquenty\Entries\Entry;
use Illuminate\Http\Request;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\CP\Breadcrumbs;
use Statamic\Exceptions\BlueprintNotFoundException;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\Collections\EntriesController as StatamicEntriesController;
use Statamic\Http\Resources\CP\Entries\Entry as EntryResource;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;

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

        if ($collection->dated()) {
            $values['date'] = substr(now()->toDateTimeString(), 0, 10);
        }

        $viewData = [
            'title' => __('Create Entry'),
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
            'localizations' => $collection->sites()->map(function ($handle) use ($collection, $site, $blueprint) {
                return [
                    'handle' => $handle,
                    'name' => Site::get($handle)->name(),
                    'active' => $handle === $site->handle(),
                    'exists' => false,
                    'published' => false,
                    'url' => cp_route('eloquenty.collections.entries.create', [$collection->handle(), $handle]),
                    'livePreviewUrl' => $collection->route($handle) ? cp_route(
                        'eloquenty.collections.entries.preview.create',
                        [$collection->handle(), $handle]
                    ) : null, // Eloquenty: Use eloquenty route for preview
                ];
            })->all(),
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

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate(\Statamic\Facades\Entry::createRules($collection, $site));

        $values = $fields->process()->values()->except(['slug', 'date', 'blueprint']);

        // Eloquenty: Create Eloquenty Entry
        $entry = app(Entry::class)
            ->collection($collection)
            ->blueprint($request->_blueprint)
            ->locale($site->handle())
            ->published($request->get('published'))
            ->slug($request->slug)
            ->data($values);

        if ($collection->dated()) {
            $entry->date($this->toCarbonInstanceForSaving($request->date));
        }

        // Eloquenty: Structures are disabled for eloquenty collections
        //if (($structure = $collection->structure()) && ! $collection->orderable()) {
        //    $tree = $structure->in($site->handle());
        //    $parent = $values['parent'] ?? null;
        //    $entry->afterSave(function ($entry) use ($parent, $tree) {
        //        $tree->appendTo($parent, $entry)->save();
        //    });
        //}

        if ($entry->revisionsEnabled()) {
            $entry->store([
                'message' => $request->message,
                'user' => User::current(),
            ]);
        } else {
            $entry->updateLastModified(User::current())->save();
        }

        return new EntryResource($entry);
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
            'localizations' => $collection->sites()->map(function ($handle) use ($entry) {
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
            })->all(),
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
        return view(
            'eloquenty::entries.edit',
            array_merge($viewData, [
                'entry' => $entry,
            ])
        );
    }

    public function update(Request $request, $collection, $entry)
    {
        $this->authorize('update', $entry);

        $entry = $entry->fromWorkingCopy();

        $fields = $entry->blueprint()->fields()->addValues($request->except('id'));

        $fields->validate(\Statamic\Facades\Entry::updateRules($collection, $entry));

        $values = $fields->process()->values();

        if ($explicitBlueprint = $values->pull('blueprint')) {
            $entry->blueprint($explicitBlueprint);
        }

        $values = $values->except(['slug', 'date']);

        // Eloquenty: Structures are disabled for eloquenty collections
        //$parent = $values->pull('parent');

        if ($entry->hasOrigin()) {
            $entry->data($values->only($request->input('_localized')));
        } else {
            $entry->merge($values);
        }

        $entry->slug($request->slug);

        if ($entry->collection()->dated()) {
            $entry->date($this->toCarbonInstanceForSaving($request->date));
        }

        // Eloquenty: Structures are disabled for eloquenty collections
        //if ($collection->structure() && ! $collection->orderable()) {
        //    $entry->afterSave(function ($entry) use ($parent) {
        //        $entry->structure()
        //            ->in($entry->locale())
        //            ->move($entry->id(), $parent)
        //            ->save();
        //    });
        //}

        if ($entry->revisionsEnabled() && $entry->published()) {
            $entry
                ->makeWorkingCopy()
                ->user(User::current())
                ->save();
        } else {
            if (!$entry->revisionsEnabled() && User::current()->can('publish', $entry)) {
                $entry->published($request->published);
            }

            $entry->updateLastModified(User::current())->save();
        }

        return new EntryResource($entry);
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
