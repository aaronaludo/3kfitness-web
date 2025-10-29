<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'role_id' => 3,
            'status_id' => 2,
            'first_name' => 'Member',
            'last_name' => 'Test',
            'address' => 'Taytay',
            'phone_number' => '09557735516',
            'email' => 'member@gmail.com',
            'password' => bcrypt('member123')
        ]);
        User::create([
            'role_id' => 2,
            'status_id' => 2,
            'first_name' => 'Staff',
            'last_name' => 'Test',
            'address' => 'Cainta',
            'phone_number' => '09557735517',
            'email' => 'staff@gmail.com',
            'password' => bcrypt('staff123')
        ]);
        User::create([
            'role_id' => 1,
            'status_id' => 2,
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'address' => 'Antipolo',
            'phone_number' => '09557735518',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin123')
        ]);
        User::create([
            'role_id' => 4,
            'status_id' => 2,
            'first_name' => 'Super Admin',
            'last_name' => 'Test',
            'address' => 'San Mateo',
            'phone_number' => '09557735519',
            'email' => 'superadmin@gmail.com',
            'password' => bcrypt('superadmin123')
        ]);
        User::create([
            'role_id' => 5,
            'status_id' => 2,
            'first_name' => 'Trainer',
            'last_name' => 'Test',
            'address' => 'San Mateo',
            'phone_number' => '09557735510',
            'email' => 'trainer@gmail.com',
            'password' => bcrypt('trainer123')
        ]);
    }
}
