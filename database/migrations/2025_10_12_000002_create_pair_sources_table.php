<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pair_sources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pair_id');
            $table->string('provider');
            $table->string('provider_symbol');
            $table->integer('priority')->default(1);
            $table->enum('status', ['pending','valid','invalid'])->default('pending');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique(['pair_id', 'provider']);
            $table->index('pair_id');
            $table->foreign('pair_id')->references('id')->on('pairs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pair_sources');
    }
};





