<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->decimal('portfolio_fee_percent', 10, 4)->default(0)->after('min_deposit_amount')
                ->comment('% fee for portfolio → account transfer');
            $table->decimal('portfolio_fee_fixed', 18, 8)->default(0)->after('portfolio_fee_percent')
                ->comment('Fixed fee for portfolio → account transfer');
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['portfolio_fee_percent', 'portfolio_fee_fixed']);
        });
    }
};
