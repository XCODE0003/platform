<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Pair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Repositories\BarsRepository;

class MarketDataController extends Controller
{
    public function pairInfo(Request $request)
    {
        $validated = $request->validate([
            'pair_id' => ['required','integer','exists:pairs,id'],
        ]);

        $pair = Pair::with(['currencyIn','currencyOut'])->findOrFail($validated['pair_id']);
        $display = ($pair->currencyIn->symbol ?? $pair->currencyIn->name ?? 'BASE')
            .'/'
            .($pair->currencyOut->symbol ?? $pair->currencyOut->name ?? 'QUOTE');

        return response()->json([
            'pair_id' => $pair->id,
            'display' => $display,
        ]);
    }

    public function bars(Request $request)
    {
        $validated = $request->validate([
            'pair_id' => ['required','integer','exists:pairs,id'],
            'resolution' => ['required','string'],
            'from' => ['required','integer'],
            'to' => ['required','integer'],
            'source' => ['sometimes','string'],
            'default_source' => ['sometimes','string'],
        ]);

        $pair = Pair::with(['sources' => function ($q) use ($request) {
            $q->orderByRaw('provider = ? desc', [$request->input('default_source')])
              ->orderBy('priority','asc');
        }])->findOrFail($validated['pair_id']);

        $preferred = $validated['source'] ?? $validated['default_source'] ?? null;
        if ($preferred) {
            $source = $pair->sources->firstWhere('provider', $preferred);
        }
        if (empty($source)) {
            $source = $pair->sources->firstWhere('provider', $pair->default_source)
                ?: $pair->sources->sortBy('priority')->first();
        }

        if (!$source) {
            return response()->json(['error' => 'no_source'], 422);
        }

        // Map TV resolution to DB interval and step
        [$dbInterval, $stepMs] = $this->mapResolution((string) $validated['resolution']);
        $fromMs = (int) $validated['from'] * 1000;
        $toMs = (int) $validated['to'] * 1000;

        // 1) Try read from DB
        $rows = DB::table('bars')
            ->where('pair_id', $pair->id)
            ->where('interval', $dbInterval)
            ->whereBetween('time', [$fromMs, $toMs])
            ->orderBy('time')
            ->get();

        $missingSegments = $this->detectMissingSegments($rows, $fromMs, $toMs, $stepMs);

        // 2) If missing, backfill only missing segments from provider, then upsert
        if (!empty($missingSegments)) {
            $providerMap = config('marketdata.providers');
            $providerCfg = $providerMap[$source->provider] ?? null;
            if (!$providerCfg) {
                return response()->json(['error' => 'provider_unknown'], 422);
            }
            /** @var \App\Services\MarketData\MarketDataSource $adapter */
            $adapter = app($providerCfg['class']);
            $repo = new BarsRepository();

            foreach ($missingSegments as [$segFromMs, $segToMs]) {
                $segFromSec = (int) floor($segFromMs / 1000);
                $segToSec = (int) floor($segToMs / 1000);
                if ($segToSec <= $segFromSec) {
                    continue;
                }
                $fetched = $adapter->getBars(
                    $source->provider_symbol,
                    (string) $validated['resolution'],
                    $segFromSec,
                    $segToSec
                );
                if (!empty($fetched)) {
                    $repo->upsertBars($pair->id, $dbInterval, $fetched);
                }
            }

            // re-read after upsert
            $rows = DB::table('bars')
                ->where('pair_id', $pair->id)
                ->where('interval', $dbInterval)
                ->whereBetween('time', [$fromMs, $toMs])
                ->orderBy('time')
                ->get();
        }

        // 3) Return DB rows in TV format
        $bars = $rows->map(function ($r) {
            return [
                'time' => (int) $r->time,
                'open' => (float) $r->open,
                'high' => (float) $r->high,
                'low' => (float) $r->low,
                'close' => (float) $r->close,
                'volume' => (float) $r->volume,
            ];
        })->values()->all();

        return response()->json(['bars' => $bars]);
    }

    private function mapResolution(string $res): array
    {
        $map = [
            '1' => ['1m', 60_000],
            // '3' => ['3m', 180_000],
            // '5' => ['5m', 300_000],
            // '15' => ['15m', 900_000],
            // '30' => ['30m', 1_800_000],
            // '60' => ['1h', 3_600_000],
            // '120' => ['2h', 7_200_000],
            // '240' => ['4h', 14_400_000],
            // '1D' => ['1d', 86_400_000],
            // 'D' => ['1d', 86_400_000],
            // '1440' => ['1d', 86_400_000],
        ];
        return $map[$res] ?? ['1d', 86_400_000];
    }

    private function detectMissingSegments($rows, int $fromMs, int $toMs, int $stepMs): array
    {
        $segments = [];
        $align = function (int $t) use ($stepMs): int {
            return (int) (intdiv($t, $stepMs) * $stepMs);
        };
        $cursor = $align($fromMs);

        if ($rows->isEmpty()) {
            $segments[] = [$cursor, $toMs];
            return $segments;
        }

        foreach ($rows as $row) {
            $t = (int) $row->time;
            if ($t > $cursor) {
                $missingTo = $t - $stepMs;
                if ($missingTo >= $cursor) {
                    $segments[] = [$cursor, $missingTo];
                }
            }
            $cursor = $t + $stepMs;
        }

        if ($cursor <= $toMs) {
            $segments[] = [$cursor, $toMs];
        }

        return $segments;
    }
}


