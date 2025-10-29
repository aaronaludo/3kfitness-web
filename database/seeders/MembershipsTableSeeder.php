<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Membership;

class MembershipsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Membership::create([
            'name' => 'Full Membership (1 Month)',
            'currency' => "PHP",
            'price' =>  "600",
            'year' => 1,
        ]);
        Membership::create([
            'name' => 'Half Membership (1 Month)',
            'currency' => "PHP",
            'price' =>  "300",
            'month' => 6
        ]); 
    }
}
