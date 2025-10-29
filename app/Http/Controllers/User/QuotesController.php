<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\QuoteSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class QuotesController extends Controller
{
    public function ensureRelay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pair_id' => ['required', 'integer', 'exists:pairs,id'],
            'resolution' => ['required', 'string'],
            'ttl' => ['nullable', 'integer', 'min:60', 'max:3600'],
        ]);

        $pairId = (int) $validated['pair_id'];
        $res = (string) $validated['resolution'];
        $ttl = (int) ($validated['ttl'] ?? 600);

        $expiresAt = now()->addSeconds($ttl);

        QuoteSubscription::updateOrCreate(
            ['pair_id' => $pairId, 'resolution' => $res],
            ['expires_at' => $expiresAt]
        );

        $key = sprintf('relay:%d:%s', $pairId, $res);
        Log::info('quotes: ensure relay', ['key' => $key, 'ttl' => $ttl, 'user_id' => optional($request->user())->id]);

        return response()->json(['status' => 'ok', 'key' => $key, 'ttl' => $ttl]);
    }
}


