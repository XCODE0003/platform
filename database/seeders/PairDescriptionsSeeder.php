<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PairDescriptionsSeeder extends Seeder
{
    public function run(): void
    {
        // [currency_in symbol => description]
        $descriptions = [
            // ── Crypto ────────────────────────────────────────────────────────
            'BTC'   => 'Bitcoin – Decentralized Digital Currency',
            'ETH'   => 'Ethereum – Smart Contract Platform',
            'BNB'   => 'BNB – Binance Exchange Token',
            'SOL'   => 'Solana – High-Speed Blockchain',
            'XRP'   => 'XRP – Cross-Border Payments Network',
            'ADA'   => 'Cardano – Proof-of-Stake Blockchain',
            'DOGE'  => 'Dogecoin – Meme-Origin Cryptocurrency',
            'TRX'   => 'TRON – Decentralized Entertainment Platform',
            'TON'   => 'Toncoin – Telegram Open Network',
            'DOT'   => 'Polkadot – Multi-Chain Interoperability',
            'MATIC' => 'Polygon – Ethereum Layer-2 Scaling',
            'LINK'  => 'Chainlink – Decentralized Oracle Network',
            'LTC'   => 'Litecoin – Peer-to-Peer Digital Cash',
            'BCH'   => 'Bitcoin Cash – Bitcoin Fork for Payments',
            'ATOM'  => 'Cosmos – Internet of Blockchains',
            'ETC'   => 'Ethereum Classic – Original Ethereum Chain',
            'XLM'   => 'Stellar – Low-Cost Cross-Border Payments',
            'APT'   => 'Aptos – Next-Gen Layer-1 Blockchain',
            'OP'    => 'Optimism – Ethereum Optimistic Rollup',
            'ARB'   => 'Arbitrum – Ethereum Layer-2 Network',
            'AVAX'  => 'Avalanche – Fast Finality Smart Contracts',
            'NEAR'  => 'NEAR Protocol – Developer-Friendly Blockchain',
            'FIL'   => 'Filecoin – Decentralized Storage Network',
            'SAND'  => 'The Sandbox – Metaverse Gaming Platform',
            'AAVE'  => 'Aave – Decentralized Lending Protocol',
            'ICP'   => 'Internet Computer – Decentralized Cloud',
            'FTM'   => 'Fantom – Fast EVM-Compatible Chain',
            'GALA'  => 'Gala Games – Web3 Gaming Ecosystem',
            'INJ'   => 'Injective – DeFi & Derivatives Chain',
            'RUNE'  => 'THORChain – Cross-Chain Liquidity Protocol',
            'SUI'   => 'Sui – Object-Oriented Layer-1 Blockchain',
            'SEI'   => 'Sei Network – Trading-Optimized Blockchain',
            'TIA'   => 'Celestia – Modular Data Availability Layer',
            'WIF'   => 'dogwifhat – Solana Meme Token',
            'SHIB'  => 'Shiba Inu – Ethereum-Based Meme Token',
            'PEPE'  => 'Pepe – Frog-Themed Meme Token',
            'JUP'   => 'Jupiter – Solana DEX Aggregator',
            'PYTH'  => 'Pyth Network – On-Chain Price Oracle',
            'AEVO'  => 'Aevo – Decentralized Options Exchange',
            'ORDI'  => 'Ordinals – Bitcoin BRC-20 Token',

            // ── Metals ────────────────────────────────────────────────────────
            'XAU'   => 'Gold Spot',
            'XAG'   => 'Silver Spot',
            'XPT'   => 'Platinum Spot',
            'XPD'   => 'Palladium Spot',
            'HG'    => 'Copper Futures',

            // ── Commodities ───────────────────────────────────────────────────
            'USOIL' => 'WTI Crude Oil Futures',
            'UKOIL' => 'Brent Crude Oil Futures',
            'NATGAS'=> 'Natural Gas Futures',
            'HTOIL' => 'Heating Oil Futures',
            'GASOL' => 'Gasoline (RBOB) Futures',
            'COAL'  => 'Coal (Peabody Energy)',
            'WHEAT' => 'Wheat Futures',
            'CORN'  => 'Corn Futures',
            'SOYBN' => 'Soybeans Futures',
            'COFEE' => 'Coffee Futures',
            'SUGAR' => 'Sugar #11 Futures',
            'COTTO' => 'Cotton #2 Futures',
            'LUMOIL'=> 'Lumber Futures',
            'COCOA' => 'Cocoa Futures',
            'LIVCAT'=> 'Live Cattle Futures',

            // ── Indices ───────────────────────────────────────────────────────
            'SPX'   => 'S&P 500 Index',
            'NDX'   => 'NASDAQ 100 Index',
            'DJI'   => 'Dow Jones Industrial Average',
            'RUT'   => 'Russell 2000 Small-Cap Index',
            'VIX'   => 'CBOE Volatility Index',
            'FTSE'  => 'FTSE 100 Index (London)',
            'DAX'   => 'DAX 40 Index (Frankfurt)',
            'N225'  => 'Nikkei 225 Index (Tokyo)',

            // ── Forex ─────────────────────────────────────────────────────────
            'EUR'   => 'Euro / US Dollar',
            'GBP'   => 'British Pound / US Dollar',
            'JPY'   => 'Japanese Yen / US Dollar',
            'AUD'   => 'Australian Dollar / US Dollar',
            'NZD'   => 'New Zealand Dollar / US Dollar',

            // ── Stocks ────────────────────────────────────────────────────────
            'AAPL'  => 'Apple Inc. – Consumer Electronics',
            'MSFT'  => 'Microsoft Corporation – Cloud & Software',
            'NVDA'  => 'NVIDIA Corporation – Semiconductors & AI',
            'AMZN'  => 'Amazon.com, Inc. – E-Commerce & Cloud',
            'GOOG'  => 'Alphabet Inc. – Internet & Advertising',
            'META'  => 'Meta Platforms, Inc. – Social Media',
            'TSLA'  => 'Tesla, Inc. – Electric Vehicles & Energy',
            'BRK.B' => 'Berkshire Hathaway Inc. – Diversified Holding',
            'JPM'   => 'JPMorgan Chase & Co. – Global Banking',
            'V'     => 'Visa Inc. – Payment Network',
            'UNH'   => 'UnitedHealth Group – Health Insurance',
            'XOM'   => 'Exxon Mobil Corporation – Oil & Gas',
            'WMT'   => 'Walmart Inc. – Retail',
            'LLY'   => 'Eli Lilly and Company – Pharmaceuticals',
            'MA'    => 'Mastercard Inc. – Payment Network',
            'JNJ'   => 'Johnson & Johnson – Healthcare',
            'AVGO'  => 'Broadcom Inc. – Semiconductors',
            'HD'    => 'The Home Depot, Inc. – Home Improvement Retail',
            'ORCL'  => 'Oracle Corporation – Enterprise Software',
            'PG'    => 'Procter & Gamble Co. – Consumer Goods',
            'COST'  => 'Costco Wholesale Corporation – Warehouse Retail',
            'ABBV'  => 'AbbVie Inc. – Biopharmaceuticals',
            'NFLX'  => 'Netflix, Inc. – Streaming Entertainment',
            'AMD'   => 'Advanced Micro Devices – Semiconductors',
            'KO'    => 'The Coca-Cola Company – Beverages',
            'INTC'  => 'Intel Corporation – Semiconductors',
            'DIS'   => 'The Walt Disney Company – Entertainment',
            'PYPL'  => 'PayPal Holdings, Inc. – Digital Payments',
            'BABA'  => 'Alibaba Group Holding – E-Commerce (China)',
            'COIN'  => 'Coinbase Global, Inc. – Crypto Exchange',
            'UBER'  => 'Uber Technologies, Inc. – Ride-Hailing & Delivery',
            'SPOT'  => 'Spotify Technology S.A. – Music Streaming',
            'BA'    => 'The Boeing Company – Aerospace & Defense',
            'GS'    => 'Goldman Sachs Group – Investment Banking',
            'SBUX'  => 'Starbucks Corporation – Coffee Retail',
        ];

        $updated = 0;
        foreach ($descriptions as $symbol => $description) {
            $rows = DB::table('pairs')
                ->join('currencies', 'currencies.id', '=', 'pairs.currency_id_in')
                ->where('currencies.symbol', $symbol)
                ->update(['pairs.description' => $description]);
            $updated += $rows;
        }

        // Forex: USD/JPY, USD/CHF, etc. — base is USD, description depends on quote
        $forexUsdBase = [
            'JPY' => 'US Dollar / Japanese Yen',
            'CHF' => 'US Dollar / Swiss Franc',
            'CAD' => 'US Dollar / Canadian Dollar',
            'CNY' => 'US Dollar / Chinese Yuan',
            'MXN' => 'US Dollar / Mexican Peso',
            'RUB' => 'US Dollar / Russian Ruble',
            'TRY' => 'US Dollar / Turkish Lira',
        ];

        foreach ($forexUsdBase as $quoteSymbol => $description) {
            $rows = DB::table('pairs')
                ->join('currencies as cin',  'cin.id',  '=', 'pairs.currency_id_in')
                ->join('currencies as cout', 'cout.id', '=', 'pairs.currency_id_out')
                ->where('cin.symbol', 'USD')
                ->where('cout.symbol', $quoteSymbol)
                ->update(['pairs.description' => $description]);
            $updated += $rows;
        }

        $this->command->info("Pair descriptions updated: {$updated} pairs.");
    }
}
