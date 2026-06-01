<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a manager-authored comment (shown to the client in their cabinet
     * transaction history) and an optional link to the topped-up bill.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'bill_id')) {
                $table->unsignedBigInteger('bill_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('transactions', 'comment')) {
                $table->text('comment')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'comment')) {
                $table->dropColumn('comment');
            }
            if (Schema::hasColumn('transactions', 'bill_id')) {
                $table->dropColumn('bill_id');
            }
        });
    }
};
