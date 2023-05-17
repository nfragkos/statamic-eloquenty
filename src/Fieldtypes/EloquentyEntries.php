<?php

namespace Eloquenty\Fieldtypes;

use Eloquenty\Facades\Eloquenty;
use Statamic\Contracts\Data\Localization;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Fieldtypes\Entries;
use Statamic\Http\Resources\CP\Entries\Entry as EntryResource;
use Statamic\Query\OrderedQueryBuilder;
use Statamic\Query\StatusQueryBuilder;
use Statamic\Support\Arr;

class EloquentyEntries extends Entries
{
    // Eloquenty: Use entries icon
    protected $icon = 'entries';
    protected $canCreate = false;

    protected function configFieldItems(): array
    {
        return [
            [
                'display' => __('Appearance & Behavior'),
                'fields' => [
                    'max_items' => [
                        'display' => __('Max Items'),
                        'instructions' => __('statamic::messages.max_items_instructions'),
                        'min' => 1,
                        'type' => 'integer',
                    ],
                    'mode' => [
                        'display' => __('UI Mode'),
                        'instructions' => __('statamic::fieldtypes.relationship.config.mode'),
                        'type' => 'radio',
                        'default' => 'default',
                        'options' => [
                            'default' => __('Stack Selector'),
                            'select' => __('Select Dropdown'),
                            'typeahead' => __('Typeahead Field'),
                        ],
                    ],
                    'collections' => [
                        'display' => __('Collections'),
                        'instructions' => __('statamic::fieldtypes.entries.config.collections'),
                        'type' => 'select',
                        'mode' => 'select',
                        'multiple' => true,
                        'options' => Collection::all()->filter(function ($item) {
                            return in_array($item->handle(), Eloquenty::collections());
                        })->mapWithKeys(function ($item) {
                            return [$item->handle() => $item->title()];
                        })->all(),
                    ],
                ],
            ],
        ];
    }

    protected function getIndexQuery($request)
    {
        // Eloquenty: Use Eloquenty query builder
        $query = Eloquenty::repository()->query();

        if ($search = $request->search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        if ($site = $request->site) {
            $query->where('site', $site);
        }

        if ($request->exclusions) {
            $query->whereNotIn('id', $request->exclusions);
        }

        return $query;
    }

    protected function toItemArray($id)
    {
        // Eloquenty: Use Eloquenty query builder
        if (!$entry = Eloquenty::repository()->find($id)) {
            return $this->invalidItemArray($id);
        }

        return (new EntryResource($entry))->resolve()['data'];
    }

    public function augment($values)
    {
        $site = Site::current()->handle();
        if (($parent = $this->field()->parent()) && $parent instanceof Localization) {
            $site = $parent->locale();
        }
        // Eloquenty: Use Eloquenty query builder
        $ids = (new OrderedQueryBuilder(Eloquenty::repository()->query(), $ids = Arr::wrap($values)))
            ->whereIn('id', $ids)
            ->get()
            ->map(function ($entry) use ($site) {
                return optional($entry->in($site))->id();
            })
            ->filter()
            ->all();
        // Eloquenty: Use Eloquenty query builder
        $query = (new StatusQueryBuilder(new OrderedQueryBuilder(Eloquenty::repository()->query(), $ids)))
            ->whereIn('id', $ids);

        return $this->config('max_items') === 1 ? $query->first() : $query;
    }
}
