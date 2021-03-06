@extends('statamic::layout')
@section('title', $breadcrumbs->title('Create Entry'))
@section('wrapper_class', 'max-w-3xl')

@section('content')
    <base-entry-create-form
        :actions="{{ json_encode($actions) }}"
        collection-handle="{{ $collection }}"
        :collection-has-routes="{{ Statamic\Support\Str::bool($collectionHasRoutes) }}"
        :fieldset="{{ json_encode($blueprint) }}"
        :values="{{ json_encode($values) }}"
        :meta="{{ json_encode($meta) }}"
        :published="{{ json_encode($published) }}"
        :localizations="{{ json_encode($localizations) }}"
        :revisions="false"
        :breadcrumbs="{{ $breadcrumbs->toJson() }}"
        site="{{ $locale }}"
        create-another-url="{{ cp_route('eloquenty.collections.entries.create', [$collection, $locale, 'blueprint' => $blueprint['handle']]) }}"
        listing-url="{{ cp_route('eloquenty.collections.show', $collection) }}"
        :can-manage-publish-state="{{ Statamic\Support\Str::bool($canManagePublishState) }}"
    ></base-entry-create-form>
@endsection
