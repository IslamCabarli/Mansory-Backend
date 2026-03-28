<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FixUserPasswordSeeder extends Seeder
{
    public function run(): void
    {
        // Mövcud admini tap və ya yenisini yarat
        User::updateOrCreate(
            ['email' => 'admin@mansory.com'], // Bu email-li istifadəçini axtar
            [
                'name' => 'Admin Mansory',
                'role' => 'admin',
                'password' => Hash::make('password123'), // Yeni şifrə: password123
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin parolu uğurla yeniləndi (password123)');
    }
}