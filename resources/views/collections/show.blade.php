@extends('statamic::layout')
@section('title', Statamic::crumb($collection->title(), 'Eloquenty Collections'))
@section('wrapper_class', 'max-w-full')

@section('content')
    <collection-view
        title="{{ $collection->title() }}"
        handle="{{ $collection->handle() }}"
        breadcrumb-url="{{ cp_route('eloquenty.collections.index') }}"
        :can-create="@can("create {$collection->handle()} entries", $collection) true @else false @endcan"
        :create-urls='@json($createUrls)'
        :blueprints="{{ json_encode($blueprints) }}"
        sort-column="{{ $collection->sortField() }}"
        sort-direction="{{ $collection->sortDirection() }}"
        :columns="{{ $columns->toJson() }}"
        :filters="{{ $filters->toJson() }}"
        action-url="{{ cp_route('eloquenty.collections.entries.actions.run', $collection->handle()) }}"
        {{-- reorder-url="{{ cp_route('collections.entries.reorder', $collection->handle()) }}" --}}
        initial-site="{{ $site }}"
        :sites='{{ json_encode($sites) }}'
    >
    </collection-view>
@endsection
