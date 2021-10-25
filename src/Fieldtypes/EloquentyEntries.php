<?php

namespace Eloquenty\Fieldtypes;

use Eloquenty\Facades\Eloquenty;
use Statamic\Contracts\Data\Localization;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Fieldtypes\Entries;
use Statamic\Http\Resources\CP\Entries\Entry as EntryResource;

class EloquentyEntries extends Entries
{
    // Eloquenty: Use entries icon
    protected $icon = 'entries';

    protected function configFieldItems(): array
    {
        return array_merge(parent::configFieldItems(), [
            'create' => [
                'display' => __('Allow Creating'),
                'instructions' => __('statamic::fieldtypes.entries.config.create'),
                'type' => 'toggle',
                'default' => false,
                'width' => 100,
            ],
            // Show Eloquenty collections
            'collections' => [
                'display' => 'Eloquenty ' . __('Collections'),
                'mode' => 'select',
                'multiple' => true,
                'options' => Collection::all()->filter(function ($item) {
                    return in_array($item->handle(), Eloquenty::collections());
                })->mapWithKeys(function ($item) {
                    return [$item->handle() => $item->title()];
                })->all(),
                'type' => 'select',
                'width' => 100,
            ],
        ]);
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

        return (new EntryResource($entry))->resolve();
    }

    protected function augmentValue($value)
    {
        if (!is_object($value)) {
            // Eloquenty: Use Eloquenty repository
            $value = Eloquenty::repository()->find($value);
        }

        if ($value != null && $parent = $this->field()->parent()) {
            $site = $parent instanceof Localization ? $parent->locale() : Site::current()->handle();
            $value = $value->in($site);
        }

        return ($value && $value->status() === 'published') ? $value : null;
    }
}
