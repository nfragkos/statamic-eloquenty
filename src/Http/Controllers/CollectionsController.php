<?php

namespace Eloquenty\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Eloquenty\Facades\Eloquenty;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\CP\Column;
use Statamic\Facades\Collection;
use Statamic\Facades\Scope;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\Collections\CollectionsController as StatamicCollectionsController;

class CollectionsController extends StatamicCollectionsController
{
    public function index(): View
    {
        $this->authorize('index', CollectionContract::class, __('You are not authorized to view collections.'));

        $collections = Collection::all()->filter(function ($collection) {
            // Eloquenty: Filter non eloquenty collections
            return in_array($collection->handle(), Eloquenty::collections()) && User::current()->can('view', $collection);
        })->map(function ($collection) {
            return [
                'id' => $collection->handle(),
                'title' => $collection->title(),
                'entries' => $collection->queryEloquentyEntries()->where('site', Site::selected())->count(),
                'edit_url' => $collection->editUrl(),
                'delete_url' => $collection->deleteUrl(),
                'entries_url' => cp_route('eloquenty.collections.show', $collection->handle()),
                'blueprints_url' => cp_route('collections.blueprints.index', $collection->handle()),
                'scaffold_url' => cp_route('collections.scaffold', $collection->handle()),
                'deleteable' => false, //User::current()->can('delete', $collection),  // Eloquenty: Operation not for eloquenty
                'editable' => false, //User::current()->can('edit', $collection), // Eloquenty: Operation not for eloquenty
                'blueprint_editable' => false, //User::current()->can('configure fields'), // Eloquenty: Operation not for eloquenty
            ];
        })->values();
        // Eloquenty: Use eloquenty view
        return view('eloquenty::collections.index', [
            'collections' => $collections,
            'columns' => [
                Column::make('title')->label(__('Title')),
                Column::make('entries')->label(__('Entries')),
            ],
        ]);
    }

    public function show(Request $request, $collection): View
    {
        $this->authorize('view', $collection, __('You are not authorized to view this collection.'));

        $blueprints = $collection
            ->entryBlueprints()
            ->reject->hidden()
            ->map(function ($blueprint) {
                return [
                    'handle' => $blueprint->handle(),
                    'title' => $blueprint->title(),
                ];
            })->values();

        $site = $request->site ? Site::get($request->site) : Site::selected();

        $columns = $collection
            ->entryBlueprint()
            ->columns()
            ->setPreferred("collections.{$collection->handle()}.columns")
            ->rejectUnlisted()
            ->values();

        $viewData = [
            'collection' => $collection,
            'blueprints' => $blueprints,
            'site' => $site->handle(),
            'columns' => $columns,
            'filters' => Scope::filters('entries', [
                'collection' => $collection->handle(),
                'blueprints' => $blueprints->pluck('handle')->all(),
            ]),
            'sites' => $collection->sites()->map(function ($site) {
                $site = Site::get($site);

                return [
                    'handle' => $site->handle(),
                    'name' => $site->name(),
                ];
            })->values()->all(),
            'createUrls' => $collection->sites()
                ->mapWithKeys(fn ($site) => [$site => cp_route('collections.entries.create', [$collection->handle(), $site])])
                ->all(),
        ];

        // Eloquenty: Operation not for eloquenty
        //if ($collection->queryEloquentyEntries()->count() === 0) {
        //    return view('statamic::collections.empty', $viewData);
        //}

        // Eloquenty: Structures are disabled for eloquenty collections
        //if (! $collection->hasStructure()) {
        //    return view('statamic::collections.show', $viewData);
        //}
        //
        //$structure = $collection->structure();
        //
        //return view('statamic::collections.show', array_merge($viewData, [
        //    'structure' => $structure,
        //    'expectsRoot' => $structure->expectsRoot(),
        //]));

        // Eloquenty: Use eloquenty view
        return view('eloquenty::collections.show', $viewData);
    }
}
