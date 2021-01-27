<?php

namespace Nfragkos\Eloquenty\Entries;

use Statamic\Stache\Query\QueriesTaxonomizedEntries as StatamicQueriesTaxonomizedEntries;

trait QueriesTaxonomizedEntries
{
    use StatamicQueriesTaxonomizedEntries;

    // Eloquenty: Fix where conditions for taxonomies
    public function whereTaxonomy($term)
    {
        $exploded = explode('::', $term);
        $this->builder->where('data->' . $exploded[0], 'like', '%' . $exploded[1] . '%');

        return $this;
    }

    // Eloquenty: Fix where conditions for taxonomies
    public function whereTaxonomyIn($terms)
    {
        $this->builder->where(function ($query) use ($terms) {
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

        return $this;
    }
}
