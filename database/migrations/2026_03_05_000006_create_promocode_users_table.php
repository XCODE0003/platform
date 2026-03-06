<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promocode_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promocode_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('used_at')->useCurrent();

            $table->unique(['promocode_id', 'user_id']);
            $table->foreign('promocode_id')->references('id')->on('promocodes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promocode_users');
    }
};
