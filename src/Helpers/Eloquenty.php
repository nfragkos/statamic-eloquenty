<?php

namespace Nfragkos\Eloquenty\Helpers;

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
}
