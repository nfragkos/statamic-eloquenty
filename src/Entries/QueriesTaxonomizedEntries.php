<?php

namespace Eloquenty\Entries;

use Illuminate\Database\Eloquent\Builder;
use Statamic\Stache\Query\QueriesTaxonomizedEntries as StatamicQueriesTaxonomizedEntries;

trait QueriesTaxonomizedEntries
{
    use StatamicQueriesTaxonomizedEntries;

    // Eloquenty: Fix where conditions for taxonomies
    public function whereTaxonomy($term)
    {
        $this->builder->where(function (Builder $query) use ($term) {
            $exploded = explode('::', $term);

            $query->where('data->' . $exploded[0], 'like', '%' . $exploded[1] . '%');

            // Eloquenty: Check origin taxonomies
            $query->orWhereHas('origin', function ($query) use ($exploded) {
                $query->where('data->' . $exploded[0], 'like', '%' . $exploded[1] . '%');
            });
        });

        return $this;
    }

    // Eloquenty: Fix where conditions for taxonomies
    public function whereTaxonomyIn($terms)
    {
        $this->builder->where(function (Builder $query) use ($terms) {
            for ($i = 0; $i < count($terms); $i++) {
                $exploded = explode('::', $terms[$i]);

                if ($i === 0) {
                    $query->where(function ($query) use ($exploded) {
                        $query->where('data->' . $exploded[0], 'like', '%' . $exploded[1] . '%');
                    });
                } else {
                    $query->orWhere(function ($query) use ($exploded) {
                        $query->where('data->' . $exploded[0], 'like', '%' . $exploded[1] . '%');
                    });
                }
            }

            // Eloquenty: Check origin taxonomies
            $query->orWhereHas('origin', function ($query) use ($terms) {
                for ($i = 0; $i < count($terms); $i++) {
                    $exploded = explode('::', $terms[$i]);

                    if ($i === 0) {
                        $query->where(function ($query) use ($exploded) {
                            $query->where('data->' . $exploded[0], 'like', '%' . $exploded[1] . '%');
                        });
                    } else {
                        $query->orWhere(function ($query) use ($exploded) {
                            $query->where('data->' . $exploded[0], 'like', '%' . $exploded[1] . '%');
                        });
                    }
                }
            });
        });

        return $this;
    }
}
