<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Schedule;

class SchedulesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schedule::create([
            'name' => 'Yoga',
        ]);
        Schedule::create([
            'name' => 'Boxing',
        ]);
        Schedule::create([
            'name' => 'Kick Boxing',
        ]);
    }
}
