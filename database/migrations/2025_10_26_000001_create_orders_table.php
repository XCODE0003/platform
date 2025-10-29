<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pair_id');
            $table->unsignedBigInteger('bill_id');

            $table->enum('side', ['buy', 'sell']);
            $table->enum('type', ['market', 'limit', 'stop']);
            $table->string('tif')->nullable(); // GTC/IOC/FOK
            $table->boolean('post_only')->default(false);

            $table->decimal('price', 30, 10)->nullable();
            $table->decimal('stop_price', 30, 10)->nullable();
            $table->decimal('amount', 30, 10); // base qty
            $table->decimal('total', 30, 10)->nullable(); // quote amount

            $table->enum('stops_mode', ['none', 'pips', 'price'])->default('none');
            $table->decimal('take_profit', 30, 10)->nullable();
            $table->decimal('stop_loss', 30, 10)->nullable();

            $table->enum('status', ['open', 'queued', 'filled', 'partial', 'cancelled', 'rejected'])->default('open');

            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['pair_id']);
            $table->index(['bill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};


