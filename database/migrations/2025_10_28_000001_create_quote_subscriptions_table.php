<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pair_id');
            $table->string('resolution', 10);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['pair_id', 'resolution']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_subscriptions');
    }
};

