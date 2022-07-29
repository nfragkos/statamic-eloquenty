<?php

namespace Eloquenty\Http\Controllers;

use Illuminate\Http\Request;
use Eloquenty\Entries\EntryRepository;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\FrontendController as StatamicFrontendController;
use Statamic\Statamic;
use Statamic\Support\Str;

/**
 * The front-end controller.
 */
class FrontendController extends StatamicFrontendController
{
    /**
     * Handles all URLs.
     *
     * @return string
     */
    public function index(Request $request)
    {
        $url = Site::current()->relativePath(
            str_finish($request->getUri(), '/')
        );

        if ($url === '') {
            $url = '/';
        }

        if (Statamic::isAmpRequest()) {
            $url = str_after($url, '/' . config('statamic.amp.route'));
        }

        if (Str::contains($url, '?')) {
            $url = substr($url, 0, strpos($url, '?'));
        }

        // Eloquenty: Skip Stache
        if ($data = Data::findByUri($url, Site::current()->handle())) {
           return $data;
        }

        // Eloquenty: Use Eloquenty EntryRepository to find an entry that matches current uri
        if ($entry = app(EntryRepository::class)->query()
            ->where('uri', $url)
            ->where('site', Site::current()->handle())
            ->first()) {
            return $entry;
        }

        throw new NotFoundHttpException;
    }
}
