<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE pairs MODIFY asset_class ENUM('crypto','metal','stock','forex','fiat','commodity','index') NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE pairs MODIFY asset_class ENUM('crypto','metal','stock','forex','fiat') NULL");
    }
};
