<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bars', function (Blueprint $table): void {
            $table->decimal('open', 30, 10)->change();
            $table->decimal('high', 30, 10)->change();
            $table->decimal('low', 30, 10)->change();
            $table->decimal('close', 30, 10)->change();
            $table->decimal('volume', 30, 10)->change();
            // опционально источник
            // $table->string('provider', 32)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('bars', function (Blueprint $table): void {
            $table->string('open')->change();
            $table->string('high')->change();
            $table->string('low')->change();
            $table->string('close')->change();
            $table->string('volume')->change();
            // $table->dropColumn('provider');
        });
    }
};