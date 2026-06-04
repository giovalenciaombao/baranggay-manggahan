<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'first_name'     => 'Admin',
            'last_name'      => 'Manggahan',
            'email'          => 'admin@manggahan.ph',
            'password'       => bcrypt('password'),
            'contact_number' => '09000000000',
            'address'        => 'Barangay Hall, Manggahan',
            'role'           => 'admin',
            'status'         => 'active',
        ]);

        // Test resident
        User::create([
            'first_name'     => 'Juan',
            'last_name'      => 'dela Cruz',
            'email'          => 'resident@manggahan.ph',
            'password'       => bcrypt('password'),
            'contact_number' => '09111111111',
            'address'        => 'Purok 1, Manggahan',
            'role'           => 'resident',
            'status'         => 'active',
        ]);
    }
}