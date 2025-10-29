<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Status;

class StatusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Status::create([
            'name' => 'Pending',
        ]);
        Status::create([
            'name' => 'Registered',
        ]);
        Status::create([
            'name' => 'Failed',
        ]);
        Status::create([
            'name' => 'Successful',
        ]);
    }
}
