<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Models\Bar;
use App\Models\Pair;
use InvalidArgumentException;
use Throwable;

/**
 * Authoritative market-price source for order/position pricing.
 *
 * The frontend MUST NOT be trusted for execution prices. All server-side
 * money-moving paths (market order entry, position close, TP/SL triggers)
 * resolve price through this service.
 *
 * Resolution order:
 *   1. Most recent bar close stored in the DB (fresh enough).
 *   2. Live provider fetch (Binance / TwelveData) as fallback.
 *
 * Returns price as a decimal string (never float) to preserve precision
 * across bcmath arithmetic downstream.
 */
final class MarketPriceResolver
{
    private const BAR_STALE_SECONDS = 120;

    public function __construct(
        private readonly UpdateRates $rates = new UpdateRates(),
    ) {}

    /**
     * Resolve the current market price for a pair.
     *
     * @throws InvalidArgumentException when no price can be determined.
     */
    public function resolve(Pair $pair): string
    {
        $fromBar = $this->priceFromLatestBar($pair->id);
        if ($fromBar !== null) {
            return $fromBar;
        }

        $fromProvider = $this->priceFromProvider($pair);
        if ($fromProvider !== null) {
            return $fromProvider;
        }

        throw new InvalidArgumentException(
            "Unable to resolve market price for pair #{$pair->id}"
        );
    }

    private function priceFromLatestBar(int $pairId): ?string
    {
        $bar = Bar::query()
            ->where('pair_id', $pairId)
            ->orderByDesc('time')
            ->first();

        if (!$bar) {
            return null;
        }

        $nowMs = (int) (microtime(true) * 1000);
        $barTime = (int) $bar->time;
        // `time` may be seconds or milliseconds depending on source; normalize.
        if ($barTime < 10_000_000_000) {
            $barTime *= 1000;
        }
        $ageSeconds = max(0, ($nowMs - $barTime) / 1000);
        if ($ageSeconds > self::BAR_STALE_SECONDS) {
            return null;
        }

        $close = (string) $bar->close;
        return $this->isPositiveNumeric($close) ? $close : null;
    }

    private function priceFromProvider(Pair $pair): ?string
    {
        try {
            $pair->loadMissing('currencyIn');
            $symbol = (string) ($pair->currencyIn->symbol ?? '');
            if ($symbol === '') {
                return null;
            }
            $price = $pair->default_source === 'binance'
                ? $this->rates->fetchBinancePrice($symbol . 'USDT')
                : $this->rates->fetchTwelveDataPrice($symbol);
        } catch (Throwable) {
            return null;
        }

        if ($price === null || $price <= 0) {
            return null;
        }

        // Round-trip float -> string with fixed precision to avoid
        // "1.1E-5" style scientific notation entering bcmath.
        return rtrim(rtrim(number_format($price, 10, '.', ''), '0'), '.') ?: '0';
    }

    private function isPositiveNumeric(string $s): bool
    {
        return is_numeric($s) && bccomp($s, '0', 10) > 0;
    }
}
