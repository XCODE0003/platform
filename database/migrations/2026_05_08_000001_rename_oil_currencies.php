<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Display labels only — currency `code` is intentionally kept as-is
        // to avoid breaking foreign references and pair_sources mappings.
        DB::table('currencies')->where('code', 'USOIL')->update([
            'name'   => 'WTI Crude Oil',
            'symbol' => 'WTI Crude Oil',
        ]);

        DB::table('currencies')->where('code', 'UKOIL')->update([
            'name'   => 'Brent Oil',
            'symbol' => 'Brent Oil',
        ]);
    }

    public function down(): void
    {
        DB::table('currencies')->where('code', 'USOIL')->update([
            'name'   => 'WTI Crude Oil',
            'symbol' => 'USOIL',
        ]);

        DB::table('currencies')->where('code', 'UKOIL')->update([
            'name'   => 'Brent Crude Oil',
            'symbol' => 'UKOIL',
        ]);
    }
};
