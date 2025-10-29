<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MotivationalVideo;

class MotivationalVideoController extends Controller
{
    public function index(Request $request)
    {
        $data = MotivationalVideo::all();
    
        if (!$data) {
            return response()->json(['message' => 'Motivational Videos is Empty']);
        }
    
        return response()->json(['data' => $data]);
    }
}
