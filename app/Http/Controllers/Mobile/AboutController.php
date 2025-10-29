<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\About;

class AboutController extends Controller
{
    public function index(Request $request)
    {
        $data = About::first();
    
        if (!$data) {
            return response()->json(['message' => 'About is Empty']);
        }
    
        return response()->json(['data' => $data]);
    }
}
