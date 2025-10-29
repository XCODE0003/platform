<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pair_id');
            $table->unsignedBigInteger('bill_id');
            $table->enum('side', ['buy', 'sell']);
            $table->decimal('entry_price', 30, 10);
            $table->decimal('quantity', 30, 10);
            $table->decimal('entry_total', 30, 10);
            $table->decimal('take_profit', 30, 10)->nullable();
            $table->decimal('stop_loss', 30, 10)->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->decimal('close_price', 30, 10)->nullable();
            $table->decimal('close_total', 30, 10)->nullable();
            $table->decimal('realized_pnl', 30, 10)->nullable();
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['pair_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};



