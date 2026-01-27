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
                'admin_confirmation_status' => 1
            ],
            [
                'title' => 'Customer Support',
                'description' => 'The support team was very helpful, but the response time was a bit slow.',
                'admin_confirmation_status' => 1
            ],
            [
                'title' => 'User Interface',
                'description' => 'The user interface is clean and easy to navigate, but the color scheme could be more appealing.',
                'admin_confirmation_status' => 1
            ],
            [
                'title' => 'Payment Gateway',
                'description' => 'The payment process is smooth, but adding more payment options would be great.',
                'admin_confirmation_status' => 1
            ],
            [
                'title' => 'Mobile Experience',
                'description' => 'The mobile experience is decent, but the layout feels cramped on smaller screens.',
                'admin_confirmation_status' => 1
            ]
        ];
    
        foreach ($feedbacks as $feedback) {
            Feedback::create($feedback);
        }
    }    
}
