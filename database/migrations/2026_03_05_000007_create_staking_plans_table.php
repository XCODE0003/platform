<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staking_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('apy_percent', 8, 4);
            $table->unsignedInteger('duration_days');
            $table->decimal('min_amount', 24, 10)->default(0);
            $table->decimal('max_amount', 24, 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staking_plans');
    }
};
