<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pairs', function (Blueprint $table) {
            $table->enum('asset_class', ['crypto','metal','stock','forex','fiat'])->nullable()->after('is_active');
            $table->string('default_source')->nullable()->after('asset_class');
        });
    }

    public function down(): void
    {
        Schema::table('pairs', function (Blueprint $table) {
            $table->dropColumn(['asset_class', 'default_source']);
        });
    }
};





