<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pairs', function (Blueprint $table): void {
            // Daily swap rate as a fraction (e.g. 0.0003 = 0.03% per day)
            $table->decimal('swap_rate', 10, 6)->default(0.0003)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('pairs', function (Blueprint $table): void {
            $table->dropColumn('swap_rate');
        });
    }
};
