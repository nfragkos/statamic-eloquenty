<?php

use Illuminate\Support\Facades\Route;
use Eloquenty\Http\Controllers\LocalizeEntryController;
use Eloquenty\Http\Controllers\RestoreEntryRevisionController;
use Eloquenty\Http\Controllers\CollectionsController;
use Eloquenty\Http\Controllers\EntriesController;
use Eloquenty\Http\Controllers\EntryActionController;
use Eloquenty\Http\Controllers\EntryPreviewController;
use Eloquenty\Http\Controllers\EntryRevisionsController;
use Eloquenty\Http\Controllers\PublishedEntriesController;

/**
 * CP Routes
 * Use same route structure for Eloquenty collections but prefix with eloquenty.
 */

Route::prefix('eloquenty/')->name('eloquenty.')->group(function () {
    Route::get('/collections', [CollectionsController::class, 'index'])->name('collections.index');
    Route::get('/collections/{collection}', [CollectionsController::class, 'show'])->name('collections.show');

    Route::group(['prefix' => 'collections/{collection}/entries'], function () {
        Route::get('/', [EntriesController::class, 'index'])->name('collections.entries.index');
        Route::post('actions', [EntryActionController::class, 'run'])->name('collections.entries.actions.run');
        Route::post('actions/list', [EntryActionController::class, 'bulkActions'])->name('collections.entries.actions.bulk');
        Route::get('create/{site}', [EntriesController::class, 'create'])->name('collections.entries.create');
        Route::post('create/{site}/preview', [EntryPreviewController::class, 'create'])->name('collections.entries.preview.create');
        //Route::post('reorder', 'ReorderEntriesController')->name('collections.entries.reorder'); // Structures are disabled for Eloquenty
        Route::post('{site}', [EntriesController::class, 'store'])->name('collections.entries.store');

        Route::group(['prefix' => '{entry}'], function () {
            Route::get('/', [EntriesController::class, 'edit'])->name('collections.entries.edit');
            Route::post('publish', [PublishedEntriesController::class, 'store'])->name('collections.entries.published.store');
            Route::post('unpublish', [PublishedEntriesController::class, 'destroy'])->name('collections.entries.published.destroy');
            Route::post('localize', LocalizeEntryController::class)->name('collections.entries.localize');

            Route::resource('revisions', EntryRevisionsController::class, [
                'as' => 'collections.entries',
                'only' => ['index', 'store', 'show'],
            ]);

            Route::post('restore-revision', RestoreEntryRevisionController::class)->name('collections.entries.restore-revision');
            Route::post('preview', [EntryPreviewController::class, 'edit'])->name('collections.entries.preview.edit');
            Route::get('preview', [EntryPreviewController::class, 'show'])->name('collections.entries.preview.popout');
            Route::patch('/', [EntriesController::class, 'update'])->name('collections.entries.update');
        });
    });
});
