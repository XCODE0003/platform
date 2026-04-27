<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->string('deposit_address')->nullable()->after('address_regex');
            $table->string('deposit_memo')->nullable()->after('deposit_address');
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['deposit_address', 'deposit_memo']);
        });
    }
};
