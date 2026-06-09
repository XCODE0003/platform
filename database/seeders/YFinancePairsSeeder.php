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

            // ── Tech / semiconductors / software (added) ──────────────────────
            ['ADBE',   'Adobe Inc.',                    'ADBE'],
            ['CRM',    'Salesforce, Inc.',              'CRM'],
            ['QCOM',   'Qualcomm Incorporated',         'QCOM'],
            ['IBM',    'IBM Corporation',               'IBM'],
            ['CSCO',   'Cisco Systems, Inc.',           'CSCO'],
            ['NOW',    'ServiceNow, Inc.',              'NOW'],
            ['TXN',    'Texas Instruments Inc.',        'TXN'],
            ['INTU',   'Intuit Inc.',                   'INTU'],
            ['SNOW',   'Snowflake Inc.',                'SNOW'],
            ['PLTR',   'Palantir Technologies Inc.',    'PLTR'],
            ['AI',     'C3.ai, Inc.',                   'AI'],
            ['SMCI',   'Super Micro Computer, Inc.',    'SMCI'],
            ['ANET',   'Arista Networks, Inc.',         'ANET'],
            ['AMAT',   'Applied Materials, Inc.',       'AMAT'],
            ['LRCX',   'Lam Research Corporation',      'LRCX'],
            ['MU',     'Micron Technology, Inc.',       'MU'],
            ['TSM',    'Taiwan Semiconductor (TSMC)',   'TSM'],
            ['ASML',   'ASML Holding N.V.',             'ASML'],
            ['SAP',    'SAP SE',                        'SAP'],
            ['SONY',   'Sony Group Corporation',        'SONY'],
            ['INFY',   'Infosys Limited',               'INFY'],
            ['8035.T', 'Tokyo Electron Limited',        '8035.T'],
            ['ARM',    'Arm Holdings plc',              'ARM'],
            ['TCEHY',  'Tencent Holdings Ltd.',         'TCEHY'],
            ['BIDU',   'Baidu, Inc.',                   'BIDU'],
            ['DELL',   'Dell Technologies Inc.',        'DELL'],

            // ── Financials / banks (added) ────────────────────────────────────
            ['BAC',    'Bank of America Corporation',   'BAC'],
            ['MS',     'Morgan Stanley',                'MS'],
            ['BLK',    'BlackRock, Inc.',               'BLK'],
            ['C',      'Citigroup Inc.',                'C'],
            ['WFC',    'Wells Fargo & Company',         'WFC'],
            ['AXP',    'American Express Company',      'AXP'],
            ['MCO',    "Moody's Corporation",           'MCO'],
            ['XYZ',    'Block, Inc.',                   'XYZ'],
            ['NU',     'Nu Holdings Ltd.',              'NU'],
            ['HSBC',   'HSBC Holdings plc',             'HSBC'],
            ['UBS',    'UBS Group AG',                  'UBS'],
            ['RY',     'Royal Bank of Canada',          'RY'],
            ['SAN',    'Banco Santander, S.A.',         'SAN'],
            ['WU',     'The Western Union Company',     'WU'],
            ['SFTBY',  'SoftBank Group Corp.',          'SFTBY'],

            // ── Healthcare / pharma (added) ───────────────────────────────────
            ['MRK',    'Merck & Co., Inc.',             'MRK'],
            ['PFE',    'Pfizer Inc.',                   'PFE'],
            ['ABT',    'Abbott Laboratories',           'ABT'],
            ['DHR',    'Danaher Corporation',           'DHR'],
            ['AMGN',   'Amgen Inc.',                    'AMGN'],
            ['MDT',    'Medtronic plc',                 'MDT'],
            ['BMY',    'Bristol-Myers Squibb Company',  'BMY'],
            ['GILD',   'Gilead Sciences, Inc.',         'GILD'],
            ['MRNA',   'Moderna, Inc.',                 'MRNA'],
            ['NVO',    'Novo Nordisk A/S',              'NVO'],
            ['RHHBY',  'Roche Holding AG',              'RHHBY'],
            ['NVS',    'Novartis AG',                   'NVS'],
            ['AZN',    'AstraZeneca PLC',               'AZN'],
            ['SNY',    'Sanofi',                        'SNY'],
            ['TAK',    'Takeda Pharmaceutical Co.',     'TAK'],
            ['REGN',   'Regeneron Pharmaceuticals',     'REGN'],
            ['BIIB',   'Biogen Inc.',                   'BIIB'],
            ['BNTX',   'BioNTech SE',                   'BNTX'],
            ['GSK',    'GSK plc',                       'GSK'],
            ['TMO',    'Thermo Fisher Scientific Inc.', 'TMO'],

            // ── Consumer / food & beverage / restaurants (added) ──────────────
            ['TGT',     'Target Corporation',           'TGT'],
            ['PEP',     'PepsiCo, Inc.',                'PEP'],
            ['MDLZ',    'Mondelez International',       'MDLZ'],
            ['CL',      'Colgate-Palmolive Company',    'CL'],
            ['GIS',     'General Mills, Inc.',          'GIS'],
            ['NSRGY',   'Nestlé S.A.',                  'NSRGY'],
            ['UL',      'Unilever PLC',                 'UL'],
            ['LRLCY',   "L'Oréal S.A.",                 'LRLCY'],
            ['DANOY',   'Danone S.A.',                  'DANOY'],
            ['MCD',     "McDonald's Corporation",       'MCD'],
            ['DPZ',     "Domino's Pizza, Inc.",         'DPZ'],
            ['YUM',     'Yum! Brands, Inc.',            'YUM'],
            ['CMG',     'Chipotle Mexican Grill',       'CMG'],
            ['QSR',     'Restaurant Brands Intl.',      'QSR'],
            ['WEN',     "The Wendy's Company",          'WEN'],
            ['PZZA',    "Papa John's International",    'PZZA'],

            // ── Retail / e-commerce / hardware / toys / delivery (added) ──────
            ['GPRO',    'GoPro, Inc.',                  'GPRO'],
            ['SHOP',    'Shopify Inc.',                 'SHOP'],
            ['EBAY',    'eBay Inc.',                    'EBAY'],
            ['HPQ',     'HP Inc.',                      'HPQ'],
            ['HAS',     'Hasbro, Inc.',                 'HAS'],
            ['MAT',     'Mattel, Inc.',                 'MAT'],
            ['JAKK',    'JAKKS Pacific, Inc.',          'JAKK'],
            // Code 'DASH' is already taken by the Dash cryptocurrency.
            ['DOORDASH','DoorDash, Inc.',               'DASH'],
            ['DHER',    'Delivery Hero SE',             'DHER.DE'],

            // ── Automotive (added) ────────────────────────────────────────────
            ['F',       'Ford Motor Company',           'F'],
            ['GM',      'General Motors Company',       'GM'],
            ['TM',      'Toyota Motor Corporation',     'TM'],
            ['MBGYY',   'Mercedes-Benz Group AG',       'MBGYY'],
            ['VWAGY',   'Volkswagen AG',                'VWAGY'],
            ['RACE',    'Ferrari N.V.',                 'RACE'],
            ['BYDDY',   'BYD Company Limited',          'BYDDY'],
            ['NIO',     'NIO Inc.',                     'NIO'],
            ['RIVN',    'Rivian Automotive, Inc.',      'RIVN'],
            ['LCID',    'Lucid Group, Inc.',            'LCID'],
            ['STLA',    'Stellantis N.V.',              'STLA'],

            // ── Energy / oil & gas / renewables (added) ───────────────────────
            ['CVX',     'Chevron Corporation',          'CVX'],
            ['COP',     'ConocoPhillips',               'COP'],
            ['OXY',     'Occidental Petroleum',         'OXY'],
            ['EOG',     'EOG Resources, Inc.',          'EOG'],
            ['MPC',     'Marathon Petroleum Corp.',     'MPC'],
            ['PSX',     'Phillips 66',                  'PSX'],
            ['VLO',     'Valero Energy Corporation',    'VLO'],
            ['SHEL',    'Shell plc',                    'SHEL'],
            ['BP',      'BP p.l.c.',                    'BP'],
            ['TTE',     'TotalEnergies SE',             'TTE'],
            ['ARAMCO',  'Saudi Aramco',                 '2222.SR'],
            ['PBR',     'Petrobras',                    'PBR'],
            ['EQNR',    'Equinor ASA',                  'EQNR'],
            ['RELIANCE','Reliance Industries',          'RELIANCE.NS'],
            ['SLB',     'SLB N.V.',                     'SLB'],
            ['BKR',     'Baker Hughes Company',         'BKR'],
            ['ENB',     'Enbridge Inc.',                'ENB'],
            ['FSLR',    'First Solar, Inc.',            'FSLR'],
            ['ENPH',    'Enphase Energy, Inc.',         'ENPH'],
            ['NEE',     'NextEra Energy, Inc.',         'NEE'],
            ['JKS',     'JinkoSolar Holding Co.',       'JKS'],
            ['VWS',     'Vestas Wind Systems A/S',      'VWS.CO'],
            ['ENR',     'Siemens Energy AG',            'ENR.DE'],

            // ── Media / telecom / gaming (added) ──────────────────────────────
            ['WBD',     'Warner Bros. Discovery',       'WBD'],
            ['CMCSA',   'Comcast Corporation',          'CMCSA'],
            ['FOXA',    'Fox Corporation',              'FOXA'],
            ['VZ',      'Verizon Communications',       'VZ'],
            ['T',       'AT&T Inc.',                    'T'],
            ['TMUS',    'T-Mobile US, Inc.',            'TMUS'],
            ['VOD',     'Vodafone Group Plc',           'VOD'],
            ['ORAN',    'Orange S.A.',                  'ORAN'],
            ['DTEGY',   'Deutsche Telekom AG',          'DTEGY'],
            ['EA',      'Electronic Arts Inc.',         'EA'],
            ['TTWO',    'Take-Two Interactive',         'TTWO'],
            ['NTDOY',   'Nintendo Co., Ltd.',           'NTDOY'],
            ['UBSFY',   'Ubisoft Entertainment',        'UBSFY'],
            ['PSKY',    'Paramount Skydance Corp.',     'PSKY'],
            ['ROKU',    'Roku, Inc.',                   'ROKU'],
            ['ZM',      'Zoom Video Communications',    'ZM'],

            // ── REITs / real estate (added) ───────────────────────────────────
            ['PLD',     'Prologis, Inc.',               'PLD'],
            ['AMT',     'American Tower Corp.',         'AMT'],
            ['EQIX',    'Equinix, Inc.',                'EQIX'],
            ['CCI',     'Crown Castle Inc.',            'CCI'],
            ['PSA',     'Public Storage',               'PSA'],
            ['O',       'Realty Income Corporation',    'O'],
            ['WELL',    'Welltower Inc.',               'WELL'],
            ['SPG',     'Simon Property Group',         'SPG'],
            ['DLR',     'Digital Realty Trust',         'DLR'],
            ['GMG',     'Goodman Group',                'GMG.AX'],
            ['VNA',     'Vonovia SE',                   'VNA.DE'],

            // ── Materials / chemicals / mining (added) ────────────────────────
            ['LIN',     'Linde plc',                    'LIN'],
            ['FCX',     'Freeport-McMoRan Inc.',        'FCX'],
            ['NEM',     'Newmont Corporation',          'NEM'],
            ['NUE',     'Nucor Corporation',            'NUE'],
            ['DOW',     'Dow Inc.',                     'DOW'],
            ['DD',      'DuPont de Nemours, Inc.',      'DD'],
            ['BHP',     'BHP Group Limited',            'BHP'],
            ['RIO',     'Rio Tinto Group',              'RIO'],
            ['VALE',    'Vale S.A.',                    'VALE'],
            ['GLEN',    'Glencore plc',                 'GLEN.L'],
            ['BAS',     'BASF SE',                      'BAS.DE'],

            // ── Fertilizers / agri-chemicals (added) ──────────────────────────
            ['NTR',     'Nutrien Ltd.',                 'NTR'],
            ['MOS',     'The Mosaic Company',           'MOS'],
            ['YAR',     'Yara International ASA',        'YAR.OL'],
            ['CF',      'CF Industries Holdings',       'CF'],
            ['SQM',     'Sociedad Química y Minera',     'SQM'],
            ['SDF',     'K+S AG',                        'SDF.DE'],
            ['ICL',     'ICL Group Ltd',                 'ICL'],
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
