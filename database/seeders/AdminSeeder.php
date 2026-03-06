<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = config('admins.accounts', []);

        if (empty($admins)) {
            $email = env('ADMIN_EMAIL', 'admin@example.com');
            $password = env('ADMIN_PASSWORD', 'password');
            $admins = [['email' => $email, 'password' => $password]];
        }

        foreach ($admins as $admin) {
            $email = is_array($admin) ? ($admin['email'] ?? null) : $admin;
            $password = is_array($admin) ? ($admin['password'] ?? 'password') : 'password';

            if (!$email) {
                continue;
            }

            User::updateOrCreate(
                ['email' => $email],
                [
                    'password' => Hash::make($password),
                    'is_admin' => true,
                ]
            );
        }
    }
}
