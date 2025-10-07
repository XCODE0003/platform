<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('spreads', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('currency_id_in');
            $table->integer('currency_id_out');
            $table->string('spread_value');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spreads');
    }
};
