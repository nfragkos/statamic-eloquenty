<?php

namespace Eloquenty\Helpers;

use Eloquenty\Entries\EntryRepository;
use Statamic\Contracts\Entries\EntryRepository as RepositoryContract;

class Eloquenty
{
    public function collections(): array
    {
        return config('eloquenty.collections', []);
    }

    public function isEloquentyCollection(string $handle): bool
    {
        return in_array($handle, $this->collections(), true);
    }

    public function repository(): RepositoryContract
    {
        return app(EntryRepository::class);
    }
}
