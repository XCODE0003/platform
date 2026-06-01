<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Database\Seeder;

class BackfillUserWalletsSeeder extends Seeder
{
    /**
     * Ensure every user has a UserWallet for every currency.
     *
     * Wallets are normally created once, at registration (see
     * App\Http\Service\User\CreateWallets), for the currencies that exist at
     * that moment. Currencies seeded later (e.g. the new stocks) therefore
     * never get a wallet for already-registered users, so those assets show
     * up only in the trade terminal pair list and are missing from the
     * Portfolio / Assets screen (which lists $user->wallets()).
     *
     * This seeder backfills the gap. It is idempotent — only missing wallets
     * are inserted, with a zero balance, so it is safe to run repeatedly after
     * adding new currencies.
     */
    public function run(): void
    {
        $currencyIds = Currency::pluck('id');
        if ($currencyIds->isEmpty()) {
            $this->command->warn('No currencies found — nothing to backfill.');
            return;
        }

        $now = now();
        $created = 0;

        User::query()->select('id')->chunkById(200, function ($users) use ($currencyIds, $now, &$created) {
            foreach ($users as $user) {
                $existing = UserWallet::where('user_id', $user->id)->pluck('currency_id');
                $missing  = $currencyIds->diff($existing);
                if ($missing->isEmpty()) {
                    continue;
                }

                $rows = $missing->map(fn ($currencyId) => [
                    'user_id'            => $user->id,
                    'currency_id'        => $currencyId,
                    'balance'            => 0,
                    'pending_balance'    => 0,
                    'total_invested_usd' => 0,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ])->all();

                UserWallet::insert($rows);
                $created += count($rows);
            }
        });

        $this->command->info("Backfilled {$created} missing user wallet(s).");
    }
}
