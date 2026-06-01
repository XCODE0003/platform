<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminSeeder::class);

        $this->call(CurrenciesSeeder::class);
        $this->call(DataProvidersSeeder::class);
        $this->call(GroupPairSeeder::class);
        $this->call(BinancePairsSeeder::class);
        $this->call(YFinancePairsSeeder::class);
        $this->call(NetAppPairsSeeder::class);
        $this->call(CommoditiesPairsSeeder::class);

        // Must run last: gives every existing user a wallet for every currency
        // (including the freshly-seeded stocks) so the new assets appear in the
        // Portfolio / Assets screen, not only in the trade terminal.
        $this->call(BackfillUserWalletsSeeder::class);
    }
}
