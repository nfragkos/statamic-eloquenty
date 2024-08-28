<?php

namespace Eloquenty\Http\Controllers;

use Eloquenty\Entries\EntryRepository;
use Illuminate\Http\Request;
use Statamic\Auth\Protect\Protection;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\FrontendController as StatamicFrontendController;
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
            Str::finish($request->getUri(), '/')
        );

        if ($url === '') {
            $url = '/';
        }

        if (Str::contains($url, '?')) {
            $url = substr($url, 0, strpos($url, '?'));
        }

        if ($data = Data::findByRequestUrl($request->url())) {
            return $data;
        }

        app(Protection::class)->protect();

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
