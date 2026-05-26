<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-purchase investment lots. Each Account→Portfolio transfer creates
     * one lot so we can enforce a per-lot lock period (sell allowed only
     * after locked_until) and track remaining units via FIFO on withdrawal.
     */
    public function up(): void
    {
        Schema::create('portfolio_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('wallet_id');     // user_wallets.id
            $table->foreignId('currency_id');
            // Asset units acquired in this lot, and how many remain (reduced
            // FIFO as the user sells). Stored as decimal strings like balance.
            $table->decimal('amount', 36, 18);
            $table->decimal('remaining', 36, 18);
            // USD cost basis for this specific lot.
            $table->decimal('invested_usd', 24, 8)->default(0);
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'wallet_id']);
            $table->index(['wallet_id', 'locked_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_investments');
    }
};
