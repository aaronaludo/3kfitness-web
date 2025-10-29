<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feedback;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $searchTitle = $request->title;
    
        $feedbacks = Feedback::query()
            ->when($searchTitle, function ($query, $searchTitle) {
                return $query->where('title', 'like', "%{$searchTitle}%");
            })
            ->get();
        
        return view('admin.feedbacks.index', compact('feedbacks'));
    }    
}
