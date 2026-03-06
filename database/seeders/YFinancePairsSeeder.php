<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;
use App\Models\Pair;
use App\Models\GroupPair;
use App\Models\PairSource;

class YFinancePairsSeeder extends Seeder
{
    public function run(): void
    {
        $stockGroup  = GroupPair::firstOrCreate(['name' => 'Stocks'],        ['is_active' => true]);
        $metalGroup  = GroupPair::firstOrCreate(['name' => 'Metals'],        ['is_active' => true]);
        $forexGroup  = GroupPair::firstOrCreate(['name' => 'Forex'],         ['is_active' => true]);
        $indexGroup  = GroupPair::firstOrCreate(['name' => 'Indices'],       ['is_active' => true]);

        $usd = $this->currency('USD', 'US Dollar');

        // ── STOCKS ────────────────────────────────────────────────────────────
        // [code, name, yfinance_symbol]
        $stocks = [
            ['AAPL',  'Apple Inc.',              'AAPL'],
            ['MSFT',  'Microsoft Corporation',   'MSFT'],
            ['NVDA',  'NVIDIA Corporation',      'NVDA'],
            ['AMZN',  'Amazon.com, Inc.',        'AMZN'],
            ['GOOG',  'Alphabet Inc.',           'GOOG'],
            ['META',  'Meta Platforms, Inc.',    'META'],
            ['TSLA',  'Tesla, Inc.',             'TSLA'],
            ['BRK.B', 'Berkshire Hathaway B',   'BRK-B'],
            ['JPM',   'JPMorgan Chase & Co.',    'JPM'],
            ['V',     'Visa Inc.',               'V'],
            ['UNH',   'UnitedHealth Group',      'UNH'],
            ['XOM',   'Exxon Mobil Corporation', 'XOM'],
            ['WMT',   'Walmart Inc.',            'WMT'],
            ['LLY',   'Eli Lilly and Company',  'LLY'],
            ['MA',    'Mastercard Inc.',         'MA'],
            ['JNJ',   'Johnson & Johnson',       'JNJ'],
            ['AVGO',  'Broadcom Inc.',           'AVGO'],
            ['HD',    'The Home Depot, Inc.',    'HD'],
            ['ORCL',  'Oracle Corporation',      'ORCL'],
            ['PG',    'Procter & Gamble Co.',    'PG'],
            ['COST',  'Costco Wholesale Corp.',  'COST'],
            ['ABBV',  'AbbVie Inc.',             'ABBV'],
            ['NFLX',  'Netflix, Inc.',           'NFLX'],
            ['AMD',   'Advanced Micro Devices',  'AMD'],
            ['KO',    'Coca-Cola Company',       'KO'],
            ['INTC',  'Intel Corporation',       'INTC'],
            ['DIS',   'The Walt Disney Company', 'DIS'],
            ['PYPL',  'PayPal Holdings, Inc.',   'PYPL'],
            ['BABA',  'Alibaba Group',           'BABA'],
            ['COIN',  'Coinbase Global, Inc.',   'COIN'],
            ['UBER',  'Uber Technologies, Inc.', 'UBER'],
            ['SPOT',  'Spotify Technology S.A.', 'SPOT'],
            ['BA',    'Boeing Company',          'BA'],
            ['GS',    'Goldman Sachs Group',     'GS'],
            ['SBUX',  'Starbucks Corporation',   'SBUX'],
        ];

        foreach ($stocks as [$code, $name, $yfSym]) {
            $cur  = $this->currency($code, $name);
            $pair = Pair::firstOrCreate(
                ['currency_id_in' => $cur->id, 'currency_id_out' => $usd->id],
                ['group_id' => $stockGroup->id, 'is_active' => true, 'asset_class' => 'stock', 'default_source' => 'yfinance']
            );
            PairSource::updateOrCreate(
                ['pair_id' => $pair->id, 'provider' => 'yfinance'],
                ['provider_symbol' => $yfSym, 'priority' => 1, 'status' => 'valid', 'validated_at' => now()]
            );
        }

        // ── METALS ────────────────────────────────────────────────────────────
        // [code, name, yfinance_symbol]
        $metals = [
            ['XAU', 'Gold',      'GC=F'],
            ['XAG', 'Silver',    'SI=F'],
            ['XPT', 'Platinum',  'PL=F'],
            ['XPD', 'Palladium', 'PA=F'],
            ['HG',  'Copper',    'HG=F'],
        ];

        foreach ($metals as [$code, $name, $yfSym]) {
            $cur  = $this->currency($code, $name);
            $pair = Pair::firstOrCreate(
                ['currency_id_in' => $cur->id, 'currency_id_out' => $usd->id],
                ['group_id' => $metalGroup->id, 'is_active' => true, 'asset_class' => 'metal', 'default_source' => 'yfinance']
            );
            PairSource::updateOrCreate(
                ['pair_id' => $pair->id, 'provider' => 'yfinance'],
                ['provider_symbol' => $yfSym, 'priority' => 1, 'status' => 'valid', 'validated_at' => now()]
            );
        }

        // ── FOREX ─────────────────────────────────────────────────────────────
        // [base_code, base_name, quote_code, quote_name, yfinance_symbol]
        $forex = [
            ['EUR', 'Euro',            'USD', 'US Dollar',          'EURUSD=X'],
            ['GBP', 'British Pound',   'USD', 'US Dollar',          'GBPUSD=X'],
            ['JPY', 'Japanese Yen',    'USD', 'US Dollar',          'JPYUSD=X'],
            ['USD', 'US Dollar',       'JPY', 'Japanese Yen',       'USDJPY=X'],
            ['USD', 'US Dollar',       'CHF', 'Swiss Franc',        'USDCHF=X'],
            ['AUD', 'Australian Dollar','USD','US Dollar',          'AUDUSD=X'],
            ['USD', 'US Dollar',       'CAD', 'Canadian Dollar',    'USDCAD=X'],
            ['NZD', 'New Zealand Dollar','USD','US Dollar',         'NZDUSD=X'],
            ['USD', 'US Dollar',       'CNY', 'Chinese Yuan',       'USDCNY=X'],
            ['USD', 'US Dollar',       'MXN', 'Mexican Peso',       'USDMXN=X'],
            ['USD', 'US Dollar',       'RUB', 'Russian Ruble',      'USDRUB=X'],
            ['USD', 'US Dollar',       'TRY', 'Turkish Lira',       'USDTRY=X'],
        ];

        foreach ($forex as [$inCode, $inName, $outCode, $outName, $yfSym]) {
            $curIn  = $this->currency($inCode,  $inName);
            $curOut = $this->currency($outCode, $outName);
            $pair   = Pair::firstOrCreate(
                ['currency_id_in' => $curIn->id, 'currency_id_out' => $curOut->id],
                ['group_id' => $forexGroup->id, 'is_active' => true, 'asset_class' => 'forex', 'default_source' => 'yfinance']
            );
            PairSource::updateOrCreate(
                ['pair_id' => $pair->id, 'provider' => 'yfinance'],
                ['provider_symbol' => $yfSym, 'priority' => 1, 'status' => 'valid', 'validated_at' => now()]
            );
        }

        // ── INDICES ───────────────────────────────────────────────────────────
        // [code, name, yfinance_symbol]
        $indices = [
            ['SPX',  'S&P 500',            '^GSPC'],
            ['NDX',  'NASDAQ 100',         '^NDX'],
            ['DJI',  'Dow Jones',          '^DJI'],
            ['RUT',  'Russell 2000',       '^RUT'],
            ['VIX',  'CBOE Volatility',    '^VIX'],
            ['FTSE', 'FTSE 100',           '^FTSE'],
            ['DAX',  'DAX 40',             '^GDAXI'],
            ['N225', 'Nikkei 225',         '^N225'],
        ];

        foreach ($indices as [$code, $name, $yfSym]) {
            $cur  = $this->currency($code, $name);
            $pair = Pair::firstOrCreate(
                ['currency_id_in' => $cur->id, 'currency_id_out' => $usd->id],
                ['group_id' => $indexGroup->id, 'is_active' => true, 'asset_class' => 'index', 'default_source' => 'yfinance']
            );
            PairSource::updateOrCreate(
                ['pair_id' => $pair->id, 'provider' => 'yfinance'],
                ['provider_symbol' => $yfSym, 'priority' => 1, 'status' => 'valid', 'validated_at' => now()]
            );
        }

        $this->command->info('YFinance pairs seeded successfully.');
    }

    private function currency(string $code, string $name): Currency
    {
        return Currency::firstOrCreate(
            ['code' => $code],
            [
                'name'               => $name,
                'symbol'             => $code,
                'icon'               => $code,
                'network'            => '',
                'exchange_rate'      => '1',
                'status'             => 'active',
                'is_deposit'         => false,
                'min_deposit_amount' => '0',
                'address_regex'      => '',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]
        );
    }
}
