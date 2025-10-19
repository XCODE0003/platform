<?php

namespace App\Services\MarketData\Providers;

use App\Services\MarketData\MarketDataSource;

class FinnhubSource implements MarketDataSource
{
    public function supports(string $assetClass): bool
    {
        return in_array($assetClass, ['stock','forex','metal'], true);
    }

    public function search(string $query): array
    {
        return [];
    }

    public function validate(string $symbol): bool
    {
        return true;
    }

    public function getBars(string $symbol, string $resolution, int $from, int $to): array
    {
        return [];
    }
}





