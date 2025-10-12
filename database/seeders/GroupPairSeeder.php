<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\GroupPair;

class GroupPairSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            [
                'name' => 'Cryptocurrencies',
                'is_active' => true,
            ],
            [
                'name' => 'Stocks',
                'is_active' => true,
            ],
            [
                'name' => 'Fiat Currencies',
                'is_active' => true,
            ],
            [
                'name' => 'Metals',
                'is_active' => true,
            ],
        ];
        foreach ($groups as $group) {
            GroupPair::create($group);
        }
    }
}
