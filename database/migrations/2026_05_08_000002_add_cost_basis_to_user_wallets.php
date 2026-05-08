<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_wallets', function (Blueprint $table) {
            // Cumulative USD cost basis. Increased on account→portfolio
            // transfers (by USD-equivalent of net amount), decreased
            // proportionally on portfolio→account withdrawals.
            $table->decimal('total_invested_usd', 24, 8)->default(0)->after('pending_balance');
        });

        // Backfill: assume existing positions were bought at the current
        // exchange rate (so reported PnL starts at zero rather than at a
        // fabricated profit). Better than 0, which would make every
        // existing wallet look like 100% profit.
        DB::statement('
            UPDATE user_wallets uw
            JOIN currencies c ON c.id = uw.currency_id
            SET uw.total_invested_usd = CAST(uw.balance AS DECIMAL(24,8)) * CAST(c.exchange_rate AS DECIMAL(24,8))
            WHERE CAST(uw.balance AS DECIMAL(24,8)) > 0
        ');
    }

    public function down(): void
    {
        Schema::table('user_wallets', function (Blueprint $table) {
            $table->dropColumn('total_invested_usd');
        });
    }
};
