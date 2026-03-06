<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Applies configured spread (price markup) to bar prices.
 *
 * Spreads are matched to pairs by currency_id_in (base currency) only.
 * Priority: user-specific spread > global (user_id IS NULL).
 *
 * Date window (start_date / end_date):
 *   - Both null  → spread is always active
 *   - Only start → active from that date onwards
 *   - Only end   → active until that date
 *   - Both set   → active within [start_date, end_date]
 *
 * spread_value is a percentage, e.g. 0.5 → prices × 1.005.
 *
 * For live relay use applyToBar() — checks today's date.
 * For historical bars use applyToBarMs() — checks the bar's own timestamp date.
 */
class SpreadService
{
    /**
     * Cache structure: pair_id → [config: array|null, expires: int]
     * config = ['spread_value' => float, 'start_date' => string|null, 'end_date' => string|null]
     */
    private array $cache = [];

    private int $cacheTtl = 60;

    // ─── Live relay ──────────────────────────────────────────────────────────

    /**
     * Apply spread to a bar using TODAY's date (used in live relay commands).
     *
     * @return array{float, float, float, float}  [open, high, low, close]
     */
    public function applyToBar(int $pairId, float $open, float $high, float $low, float $close): array
    {
        $m = $this->getMultiplierForDate($pairId, now()->toDateString());
        if ($m === 1.0) {
            return [$open, $high, $low, $close];
        }
        return [
            round($open  * $m, 10),
            round($high  * $m, 10),
            round($low   * $m, 10),
            round($close * $m, 10),
        ];
    }

    // ─── Historical bars ─────────────────────────────────────────────────────

    /**
     * Apply spread to a bar using the bar's own timestamp (milliseconds).
     * Used in the bars() HTTP endpoint so spread is only applied within the configured date range.
     *
     * @return array{float, float, float, float}  [open, high, low, close]
     */
    public function applyToBarMs(int $pairId, int $barTimeMs, float $open, float $high, float $low, float $close): array
    {
        $date = date('Y-m-d', (int) ($barTimeMs / 1000));
        $m = $this->getMultiplierForDate($pairId, $date);
        if ($m === 1.0) {
            return [$open, $high, $low, $close];
        }
        return [
            round($open  * $m, 10),
            round($high  * $m, 10),
            round($low   * $m, 10),
            round($close * $m, 10),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function getMultiplierForDate(int $pairId, string $date): float
    {
        $config = $this->loadConfig($pairId);
        if ($config === null) {
            return 1.0;
        }

        if (!$this->isDateInRange($date, $config['start_date'], $config['end_date'])) {
            return 1.0;
        }

        return 1.0 + ((float) $config['spread_value'] / 100.0);
    }

    public function invalidate(?int $pairId = null): void
    {
        if ($pairId !== null) {
            unset($this->cache[$pairId]);
        } else {
            $this->cache = [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function loadConfig(int $pairId): ?array
    {
        $now = time();

        if (isset($this->cache[$pairId]) && $this->cache[$pairId]['expires'] > $now) {
            return $this->cache[$pairId]['config'];
        }

        $config = $this->fetchConfig($pairId);

        $this->cache[$pairId] = [
            'config'  => $config,
            'expires' => $now + $this->cacheTtl,
        ];

        return $config;
    }

    private function fetchConfig(int $pairId): ?array
    {
        $pair = DB::table('pairs')
            ->where('id', $pairId)
            ->select('currency_id_in')
            ->first();

        if (!$pair) {
            return null;
        }

        // User-specific spreads take priority over global (user_id IS NULL)
        $spread = DB::table('spreads')
            ->where('currency_id_in', $pair->currency_id_in)
            ->where('is_active', true)
            ->orderByRaw('user_id IS NULL ASC')
            ->select('spread_value', 'start_date', 'end_date')
            ->first();

        if (!$spread) {
            return null;
        }

        return [
            'spread_value' => (float) $spread->spread_value,
            'start_date'   => $spread->start_date,
            'end_date'     => $spread->end_date,
        ];
    }

    private function isDateInRange(string $date, ?string $startDate, ?string $endDate): bool
    {
        if ($startDate !== null && $date < $startDate) {
            return false;
        }
        if ($endDate !== null && $date > $endDate) {
            return false;
        }
        return true;
    }
}
