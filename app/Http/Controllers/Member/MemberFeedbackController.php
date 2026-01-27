<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class MemberFeedbackController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role_id != 3) {
            return response()->json(['message' => 'Member account only'], 401);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'description' => 'required|string|max:1000',
        ]);

        $feedback = Feedback::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'admin_confirmation_status' => 0,
        ]);

        return response()->json([
            'message' => 'Feedback submitted successfully',
            'feedback' => $feedback,
        ], 201);
    }
}
