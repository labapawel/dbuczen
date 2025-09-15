<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'masterwolfix@gmail.com'], // sprawdzamy czy istnieje
            [
                'name' => 'Administrator',
                'password' => Hash::make('naplet'), // hasło = "password"
                'is_admin' => 1,
            ]
        );
    }
}
