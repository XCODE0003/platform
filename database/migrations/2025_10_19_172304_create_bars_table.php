<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bars', function (Blueprint $table) {
            $table->id();
            $table->integer('pair_id');
            $table->string('interval', 8);           // <— добавить
            $table->unsignedBigInteger('time');
            $table->string('close');
            $table->string('high');
            $table->string('low');
            $table->string('open');
            $table->string('volume');
            $table->timestamps();

            $table->unique(['pair_id', 'interval', 'time'], 'bars_pair_interval_time_unique');
            $table->index(['pair_id', 'interval']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bars');
    }
};
