<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Pair;
use Illuminate\Http\Request;

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
        ]);

        $pair = Pair::with(['sources' => function ($q) use ($request) {
            $q->orderByRaw('provider = ? desc', [$request->input('default_source')])
              ->orderBy('priority','asc');
        }])->findOrFail($validated['pair_id']);

        $source = $pair->sources->firstWhere('provider', $pair->default_source)
            ?: $pair->sources->sortBy('priority')->first();

        if (!$source) {
            return response()->json(['error' => 'no_source'], 422);
        }

        $providerMap = config('marketdata.providers');
        $providerCfg = $providerMap[$source->provider] ?? null;
        if (!$providerCfg) {
            return response()->json(['error' => 'provider_unknown'], 422);
        }

        $class = $providerCfg['class'];
        /** @var \App\Services\MarketData\MarketDataSource $adapter */
        $adapter = app($class);
        $bars = $adapter->getBars(
            $source->provider_symbol,
            (string) $validated['resolution'],
            (int) $validated['from'],
            (int) $validated['to']
        );

        return response()->json(['bars' => $bars]);
    }
}


