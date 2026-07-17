<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@esewa.test'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'role' => 'admin_hq',
                'negeri' => null,
                'is_active' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'negeri@esewa.test'],
            [
                'name' => 'Negeri Johor',
                'password' => 'password',
                'role' => 'admin_negeri',
                'negeri' => 'Johor',
                'is_active' => true,
            ]
        );
    }
}
