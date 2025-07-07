<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'Admin@gmail.com',
            'password' => Hash::make('12345678'),

            'name' => 'Admin User Two',
            'email' => 'aich.altamash@gmail.com',
            'password' => Hash::make('1234567'),
        ]);

    }
}
