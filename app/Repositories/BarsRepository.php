<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class BarsRepository
{
    public function upsertBars(int $pairId, string $interval, array $bars): void
    {
        // bars: [ ['time'=>..., 'open'=>..., 'high'=>..., 'low'=>..., 'close'=>..., 'volume'=>...], ... ]
        $now = now();

        $rows = collect($bars)->map(function ($b) use ($pairId, $interval, $now) {
            return [
                'pair_id'  => $pairId,
                'interval' => $interval,
                'time'     => $b['time'],
                'open'     => $b['open'],
                'high'     => $b['high'],
                'low'      => $b['low'],
                'close'    => $b['close'],
                'volume'   => $b['volume'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        if (empty($rows)) {
            return;
        }

        DB::table('bars')->upsert(
            $rows,
            ['pair_id', 'interval', 'time'],
            ['open', 'high', 'low', 'close', 'volume', 'updated_at']
        );
    }
}
