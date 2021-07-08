<?php

use Illuminate\Support\Facades\Route;

/**
 * CP Routes
 * Use same route structure for Eloquenty collections but prefix with eloquenty.
 */

Route::prefix('eloquenty/')->name('eloquenty.')->group(function () {
    Route::get('/collections', 'CollectionsController@index')->name('collections.index');
    Route::get('/collections/{collection}', 'CollectionsController@show')->name('collections.show');

    Route::group(['prefix' => 'collections/{collection}/entries'], function () {
        Route::get('/', 'EntriesController@index')->name('collections.entries.index');
        Route::post('actions', 'EntryActionController@run')->name('collections.entries.actions.run');
        Route::post('actions/list', 'EntryActionController@bulkActions')->name('collections.entries.actions.bulk');
        Route::get('create/{site}', 'EntriesController@create')->name('collections.entries.create');
        Route::post('create/{site}/preview', 'EntryPreviewController@create')->name('collections.entries.preview.create');
        //Route::post('reorder', 'ReorderEntriesController')->name('collections.entries.reorder'); // Structures are disabled for Eloquenty
        Route::post('{site}', 'EntriesController@store')->name('collections.entries.store');

        Route::group(['prefix' => '{entry}/{slug}'], function () {
            Route::get('/', 'EntriesController@edit')->name('collections.entries.edit');
            Route::post('publish', 'PublishedEntriesController@store')->name('collections.entries.published.store');
            Route::post('unpublish', 'PublishedEntriesController@destroy')->name('collections.entries.published.destroy');
            Route::post('localize', 'LocalizeEntryController')->name('collections.entries.localize');

            Route::resource('revisions', 'EntryRevisionsController', [
                'as' => 'collections.entries',
                'only' => ['index', 'store', 'show'],
            ]);

            Route::post('restore-revision', 'RestoreEntryRevisionController')->name('collections.entries.restore-revision');
            Route::post('preview', 'EntryPreviewController@edit')->name('collections.entries.preview.edit');
            Route::get('preview', 'EntryPreviewController@show')->name('collections.entries.preview.popout');
            Route::patch('/', 'EntriesController@update')->name('collections.entries.update');
        });
    });
});
