<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->timestamp('closed_at')->nullable()->after('status');
            $table->enum('close_reason', ['manual', 'take_profit', 'stop_loss'])->nullable()->after('closed_at');
            $table->decimal('swap', 30, 10)->default(0)->after('realized_pnl');
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->dropColumn(['closed_at', 'close_reason', 'swap']);
        });
    }
};
