<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Feedback;

class FeedbacksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $feedbacks = [
            [
                'title' => 'Website Performance',
                'description' => 'The website is running smoothly, but page load times can be improved.',
                'isadminread' => 0
            ],
            [
                'title' => 'Customer Support',
                'description' => 'The support team was very helpful, but the response time was a bit slow.',
                'isadminread' => 0
            ],
            [
                'title' => 'User Interface',
                'description' => 'The user interface is clean and easy to navigate, but the color scheme could be more appealing.',
                'isadminread' => 0
            ],
            [
                'title' => 'Payment Gateway',
                'description' => 'The payment process is smooth, but adding more payment options would be great.',
                'isadminread' => 0
            ],
            [
                'title' => 'Mobile Experience',
                'description' => 'The mobile experience is decent, but the layout feels cramped on smaller screens.',
                'isadminread' => 0
            ]
        ];
    
        foreach ($feedbacks as $feedback) {
            Feedback::create($feedback);
        }
    }    
}
