<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_number', User::ACCOUNT_NUMBER_LENGTH)
                ->nullable()
                ->unique()
                ->after('email');
        });

        // Backfill existing users with random, unique account numbers.
        $length = User::ACCOUNT_NUMBER_LENGTH;
        $max = (10 ** $length) - 1;
        $used = DB::table('users')
            ->whereNotNull('account_number')
            ->pluck('account_number')
            ->all();
        $used = array_flip($used);

        DB::table('users')
            ->whereNull('account_number')
            ->orderBy('id')
            ->each(function ($user) use (&$used, $length, $max): void {
                do {
                    $number = str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
                } while (isset($used[$number]));

                $used[$number] = true;

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['account_number' => $number]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['account_number']);
            $table->dropColumn('account_number');
        });
    }
};
