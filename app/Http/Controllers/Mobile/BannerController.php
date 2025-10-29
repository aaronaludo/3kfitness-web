<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        $data = Banner::first();
    
        if (!$data) {
            return response()->json(['message' => 'Banner is Empty']);
        }
    
        return response()->json(['data' => $data]);
    }
}
