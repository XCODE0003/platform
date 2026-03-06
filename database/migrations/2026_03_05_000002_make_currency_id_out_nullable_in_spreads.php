<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('spreads', function (Blueprint $table) {
            $table->integer('currency_id_out')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('spreads', function (Blueprint $table) {
            $table->integer('currency_id_out')->nullable(false)->change();
        });
    }
};
