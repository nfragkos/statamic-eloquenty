<?php

namespace Eloquenty\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Eloquenty\Facades\Eloquenty;

class EloquentyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $route = $request->route();

        // Update Entries count value for eloquenty collections
        if ($route->getName() === 'statamic.cp.collections.index') {
            /** @var Response $response */
            $response = $next($request);

            preg_match('/:initial-rows="(.*)"/', $response->getContent(), $matches, PREG_UNMATCHED_AS_NULL);
            $json = json_decode(html_entity_decode($matches[1]));

            $rows = collect($json)->map(function ($row) {
                if (Eloquenty::isEloquentyCollection($row->id)) {
                    $row->entries = 'Managed by Eloquenty';
                }

                return $row;
            });

            $newContent = str_replace($matches[1], htmlentities($rows->toJson()), $response->getContent());
            $response->setContent($newContent);

            return $response;
        }

        // If eloquenty collection  redirect to eloquenty route
        if ($route->getName() === 'statamic.cp.collections.show' && Eloquenty::isEloquentyCollection($route->parameter('collection'))) {
            return redirect()->to(cp_route('eloquenty.collections.show', [$route->parameter('collection')]));
        }

        return $next($request);
    }
}
