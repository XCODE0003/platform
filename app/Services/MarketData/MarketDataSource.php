<?php

namespace App\Services\MarketData;

interface MarketDataSource
{
    public function supports(string $assetClass): bool;

    /**
     * @return array<int, array{symbol:string, name?:string}>
     */
    public function search(string $query): array;

    public function validate(string $symbol): bool;

    /**
     * @return array<int, array{time:int, open:float, high:float, low:float, close:float, volume:float}>
     */
    public function getBars(string $symbol, string $resolution, int $from, int $to): array;
}


