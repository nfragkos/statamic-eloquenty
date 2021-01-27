@extends('statamic::layout')
@section('title', Statamic::crumb($collection->title(), 'Eloquenty Collections'))
@section('wrapper_class', 'max-w-full')

@section('content')
    <collection-view
        title="{{ $collection->title() }}"
        handle="../eloquenty/collections/{{ $collection->handle() }}"
        breadcrumb-url="{{ cp_route('eloquenty.collections.index') }}"
        :can-create="@can("create eloquenty_{$collection->handle()} entries", $collection) true @else false @endcan"
        create-url="{{ cp_route('eloquenty.collections.entries.create', [$collection->handle(), $site]) }}"
        :blueprints="{{ json_encode($blueprints) }}"
        sort-column="date"
        sort-direction="desc"
        :columns="{{ $columns->toJson() }}"
        :filters="{{ $filters->toJson() }}"
        run-action-url="{{ cp_route('eloquenty.collections.entries.actions.run', $collection->handle()) }}"
        bulk-actions-url="{{ cp_route('eloquenty.collections.entries.actions.bulk', $collection->handle()) }}"
        {{-- reorder-url="{{ cp_route('collections.entries.reorder', $collection->handle()) }}" --}}
        initial-site="{{ $site }}"
        :sites='{{ json_encode($sites) }}'
    >
    </collection-view>
@endsection
