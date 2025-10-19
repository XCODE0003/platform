<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BinanceProxyController extends Controller
{
    private function client()
    {
        $http = Http::timeout(15);
        $disableVerify = app()->environment('local') || env('BINANCE_VERIFY_SSL', 'true') === 'false';
        if ($disableVerify) {
            $http = $http->withOptions(['verify' => false]);
        }
        return $http;
    }

    public function exchangeInfo(Request $request)
    {
        try {
            $symbol = $request->query('symbol');
            $url = 'https://api.binance.com/api/v3/exchangeInfo';
            $resp = $this->client()->get($url, array_filter(['symbol' => $symbol]));
            $contentType = $resp->header('Content-Type') ?: 'application/json';
            return response($resp->body(), $resp->status())
                ->header('Content-Type', $contentType)
                ->header('Access-Control-Allow-Origin', '*');
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'upstream_error',
                'message' => $e->getMessage(),
            ], 502)->header('Access-Control-Allow-Origin', '*');
        }
    }

    public function klines(Request $request)
    {
        try {
            $query = $request->only(['symbol','interval','startTime','endTime','limit']);
            $url = 'https://api.binance.com/api/v3/klines';
            $resp = $this->client()->get($url, $query);
            $contentType = $resp->header('Content-Type') ?: 'application/json';
            return response($resp->body(), $resp->status())
                ->header('Content-Type', $contentType)
                ->header('Access-Control-Allow-Origin', '*');
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'upstream_error',
                'message' => $e->getMessage(),
            ], 502)->header('Access-Control-Allow-Origin', '*');
        }
    }
}


