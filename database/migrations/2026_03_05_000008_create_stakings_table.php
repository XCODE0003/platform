<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stakings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staking_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('user_wallets')->cascadeOnDelete();
            $table->decimal('amount', 24, 10);
            $table->decimal('apy_rate', 8, 4);
            $table->unsignedInteger('duration_days');
            $table->decimal('reward_amount', 24, 10)->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ends_at')->useCurrent();
            $table->string('status')->default('active'); // active, completed
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stakings');
    }
};
