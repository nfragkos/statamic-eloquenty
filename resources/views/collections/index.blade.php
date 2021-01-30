@extends('statamic::layout')
@section('title', 'Eloquenty Collections')

@section('content')
    <div class="flex items-center justify-between mb-3">
        <h1>Eloquenty {{ __('Collections') }}</h1>
    </div>

    @if ($collections->count() === 0)
        <div class="card mt-6">
            <p class="lead">
                No collections specified. Please create a collection and specify its handle in config/eloquenty.php
            </p>
        </div>
    @else
        <collection-list
            :initial-rows="{{ json_encode($collections) }}"
            :initial-columns="{{ json_encode($columns) }}"
            :endpoints="{}">
        </collection-list>
    @endif

    <div class="flex justify-center text-center mt-6 hidden">
        <div class="bg-white rounded-full px-3 py-1 shadow-sm text-sm text-grey-70">
        </div>
    </div>
@endsection
