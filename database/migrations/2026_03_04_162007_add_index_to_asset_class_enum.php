<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pairs MODIFY asset_class ENUM('crypto','metal','stock','forex','fiat','index')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pairs MODIFY asset_class ENUM('crypto','metal','stock','forex','fiat')");
    }
};
