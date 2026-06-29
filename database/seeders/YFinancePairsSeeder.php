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
        $curIndexGroup = GroupPair::firstOrCreate(['name' => 'Currency indices'], ['is_active' => true]);

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
            ['8035.T', 'Tokyo Electron Limited',        'TOELY'],
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
            ['DHER',    'Delivery Hero SE',             'DLVHF'],

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
            ['VWS',     'Vestas Wind Systems A/S',      'VWDRY'],
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
            ['GMG',     'Goodman Group',                'GMGSF'],
            ['VNA',     'Vonovia SE',                   'VONOY'],

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
            ['GLEN',    'Glencore plc',                 'GLNCY'],
            ['BAS',     'BASF SE',                      'BASFY'],

            // ── Fertilizers / agri-chemicals (added) ──────────────────────────
            ['NTR',     'Nutrien Ltd.',                 'NTR'],
            ['MOS',     'The Mosaic Company',           'MOS'],
            ['YAR',     'Yara International ASA',        'YARIY'],
            ['CF',      'CF Industries Holdings',       'CF'],
            ['SQM',     'Sociedad Química y Minera',     'SQM'],
            ['SDF',     'K+S AG',                        'KPLUY'],
            ['ICL',     'ICL Group Ltd',                 'ICL'],

            // ── Aerospace / apparel / satellite (added) ───────────────────────
            ['SPCE',    'Virgin Galactic Holdings',     'SPCE'],
            ['GAP',     'The Gap, Inc.',                'GAP'],
            ['VSAT',    'Viasat, Inc.',                 'VSAT'],

            // ── Industrials / aerospace & defense (added) ─────────────────────
            // Boeing (BA) is already present in the list above.
            ['CAT',     'Caterpillar Inc.',             'CAT'],
            ['GE',      'GE Aerospace',                 'GE'],
            ['HON',     'Honeywell International',      'HON'],
            ['UPS',     'United Parcel Service',         'UPS'],
            ['FDX',     'FedEx Corporation',            'FDX'],
            ['MMM',     '3M Company',                   'MMM'],
            ['DE',      'Deere & Company',              'DE'],
            ['LMT',     'Lockheed Martin Corp.',        'LMT'],
            ['RTX',     'RTX Corporation',              'RTX'],
            ['NOC',     'Northrop Grumman Corp.',        'NOC'],
            ['SIE',     'Siemens AG',                   'SIEGY'],
            ['SBGSY',   'Schneider Electric SE',        'SBGSY'],
            ['ABB',     'ABB Ltd',                      'ABB'],
            ['EADSY',   'Airbus SE',                    'EADSY'],
            ['VLVLY',   'AB Volvo',                     'VLVLY'],
            ['DHL',     'DHL Group',                    'DHLGY'],
            ['UNP',     'Union Pacific Corporation',    'UNP'],

            // ── Travel / hotels / airlines / cruise (added) ───────────────────
            ['BKNG',    'Booking Holdings Inc.',        'BKNG'],
            ['ABNB',    'Airbnb, Inc.',                 'ABNB'],
            ['EXPE',    'Expedia Group, Inc.',          'EXPE'],
            ['TCOM',    'Trip.com Group Limited',       'TCOM'],
            ['TRIP',    'Tripadvisor, Inc.',            'TRIP'],
            ['MMYT',    'MakeMyTrip Limited',           'MMYT'],
            ['MAR',     'Marriott International',        'MAR'],
            ['HLT',     'Hilton Worldwide Holdings',    'HLT'],
            ['H',       'Hyatt Hotels Corporation',     'H'],
            ['IHG',     'InterContinental Hotels Group','IHG'],
            ['ACCYY',   'Accor SA',                     'ACCYY'],
            ['DAL',     'Delta Air Lines, Inc.',        'DAL'],
            ['UAL',     'United Airlines Holdings',     'UAL'],
            ['AAL',     'American Airlines Group',       'AAL'],
            ['LUV',     'Southwest Airlines Co.',        'LUV'],
            ['RYAAY',   'Ryanair Holdings plc',         'RYAAY'],
            ['LHA',     'Deutsche Lufthansa AG',        'DLAKY'],
            ['SINGY',   'Singapore Airlines Ltd',       'SINGY'],
            ['CCL',     'Carnival Corporation',         'CCL'],
            ['RCL',     'Royal Caribbean Group',        'RCL'],
            ['NCLH',    'Norwegian Cruise Line',        'NCLH'],
            ['JAL',     'Japan Airlines Co., Ltd.',     'JAPSY'],
            ['AF',      'Air France-KLM SA',            'AFLYY'],
            ['EMBJ',    'Embraer S.A.',                 'EMBJ'],
            ['SAMSUNG', 'Samsung Electronics Co.',      'SMSN.IL'],

            // ── Networking / optical / telecom equipment (added) ──────────────
            // Arista (ANET) already present above; Infinera (INFN) delisted
            // after the Nokia acquisition (no quotes) — intentionally omitted.
            ['CIEN',    'Ciena Corporation',            'CIEN'],
            ['NOK',     'Nokia Oyj',                    'NOK'],
            ['LITE',    'Lumentum Holdings Inc.',       'LITE'],
            ['COHR',    'Coherent Corp.',               'COHR'],
            ['ERIC',    'Ericsson',                     'ERIC'],

            // ── Apparel / footwear (added) ────────────────────────────────────
            ['LULU',    'Lululemon Athletica Inc.',     'LULU'],
            ['NKE',     'Nike, Inc.',                   'NKE'],
            ['UAA',     'Under Armour, Inc.',           'UAA'],
            ['COLM',    'Columbia Sportswear Company',  'COLM'],
            ['DECK',    'Deckers Outdoor Corporation',  'DECK'],
            ['CPRI',    'Capri Holdings Limited',       'CPRI'],
            ['VFC',     'V.F. Corporation',             'VFC'],
            ['ONON',    'On Holding AG',                'ONON'],
            ['ADS',     'Adidas AG',                    'ADDYY'],
            ['PUM',     'Puma SE',                      'PUMSY'],
            ['9983.T',  'Fast Retailing Co., Ltd.',     'FRCOY'],
            ['ITX',     'Inditex',                      'IDEXY'],
            ['LEVI',    'Levi Strauss & Co.',           'LEVI'],
            ['WWW',     'Wolverine World Wide, Inc.',   'WWW'],
            ['XTEPY',   'Xtep International Holdings Ltd ADR', 'XTEPY'],
            ['LNNGY',   'Li Ning Co Ltd ADR',                 'LNNGY'],

            // ── Software (added) ──────────────────────────────────────────────
            ['DOCU',    'DocuSign, Inc.',               'DOCU'],
            ['DBX',     'Dropbox, Inc.',                'DBX'],

            // ── Aluminium producers (added; USD ADR where liquid) ─────────────
            ['NHY',     'Norsk Hydro ASA',              'NHYDY'],
            ['AA',      'Alcoa Corporation',            'AA'],
            ['ACH',     'Aluminum Corp of China',       'ACH'],
            ['CHHQF',   'China Hongqiao Group',         'CHHQF'],
            ['CENX',    'Century Aluminum Company',     'CENX'],
            ['S32',     'South32 Limited',              'SOUHY'],
            ['CSTM',    'Constellium SE',               'CSTM'],
            ['KALU',    'Kaiser Aluminum Corporation',  'KALU'],
            // Native exchange (no liquid USD listing) — priced in local currency
            ['AMAG',    'AMAG Austria Metall AG',       'AMAG.VI'],
            ['ELHA',    'ElvalHalcor SA',               'ELHA.AT'],
            ['ALR',     'Alro S.A.',                    'ALR.RO'],
            ['HINDALCO','Hindalco Industries Limited',  'HINDALCO.NS'],
            ['VEDL',    'Vedanta Limited',              'VEDL.NS'],
            ['GRNG',    'Gränges AB',                   'GRNG.ST'],

            // ── Gold miners (added; USD listing where liquid) ─────────────────
            ['B',       'Barrick Mining Corporation',   'B'],
            ['AEM',     'Agnico Eagle Mines Limited',   'AEM'],
            ['KGC',     'Kinross Gold Corporation',     'KGC'],
            ['BTG',     'B2Gold Corp.',                 'BTG'],
            ['AGI',     'Alamos Gold Inc.',             'AGI'],
            ['EGO',     'Eldorado Gold Corporation',    'EGO'],
            ['IAG',     'IAMGOLD Corporation',          'IAG'],
            ['EQX',     'Equinox Gold Corp.',           'EQX'],
            ['CGAU',    'Centerra Gold Inc.',           'CGAU'],
            ['CDE',     'Coeur Mining, Inc.',           'CDE'],
            ['NG',      'NovaGold Resources Inc.',      'NG'],
            ['MUX',     'McEwen Mining Inc.',           'MUX'],
            ['USGO',    'U.S. GoldMining Inc.',         'USGO'],
            ['NST',     'Northern Star Resources',      'NESRF'],
            ['EVN',     'Evolution Mining Limited',     'CAHPF'],
            ['ZIJIN',   'Zijin Mining Group',           'ZIJMF'],
            ['RRL',     'Regis Resources Limited',      'RGRNF'],
            ['RMS',     'Ramelius Resources Limited',   'RMLRF'],
            ['HOC',     'Hochschild Mining plc',        'HCHDF'],
            ['FRES',    'Fresnillo plc',                'FNLPF'],
            // Native exchange (no liquid USD listing) — priced in local currency
            ['SDG',     'Shandong Gold Mining',         '1787.HK'],
            ['ZHAOJIN', 'Zhaojin Mining Industry',      '1818.HK'],
            ['PRU',     'Perseus Mining Limited',       'PRU.AX'],

            // ── Industrials / fintech / mining / software (added) ─────────────
            ['GEHC',    'GE HealthCare Technologies',   'GEHC'],
            ['GEV',     'GE Vernova Inc.',              'GEV'],
            ['OSPN',    'OneSpan Inc.',                 'OSPN'],
            ['GFI',     'Gold Fields Limited',          'GFI'],
            ['BOX',     'Box, Inc.',                    'BOX'],
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

        // ── CURRENCY INDICES ────────────────────────────────────────────────────
        // [code, name, yfinance_symbol]
        $currencyIndices = [
            ['DXY', 'US Dollar Index',                   'DX-Y.NYB'],
            ['XDE', 'Euro Currency Index',               '^XDE'],
            ['XDB', 'British Pound Currency Index',      '^XDB'],
            ['XDN', 'Japanese Yen Currency Index',       '^XDN'],
            ['XDS', 'Swiss Franc Currency Index',        '^XDS'],
            ['XDA', 'Australian Dollar Currency Index',  '^XDA'],
            ['XDZ', 'New Zealand Dollar Currency Index', '^XDZ'],
            ['XDC', 'Canadian Dollar Currency Index',    '^XDC'],
        ];

        foreach ($currencyIndices as [$code, $name, $yfSym]) {
            $cur  = $this->currency($code, $name);
            $pair = Pair::firstOrCreate(
                ['currency_id_in' => $cur->id, 'currency_id_out' => $usd->id],
                ['group_id' => $curIndexGroup->id, 'is_active' => true, 'asset_class' => 'currency_index', 'default_source' => 'yfinance']
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
