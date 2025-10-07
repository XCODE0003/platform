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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol');
            $table->string('code');
            $table->string('icon');
            $table->string('network');
            $table->string('exchange_rate');
            $table->string('status')->default('active');
            $table->boolean('is_deposit')->default(false);
            $table->string('min_deposit_amount')->default(0);
            $table->string('address_regex')->nullable();

            $table->string('created_by');
            $table->string('updated_by');
            $table->timestamps();
        });



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
