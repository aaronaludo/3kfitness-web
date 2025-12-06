<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TrainerBanner;

class TrainerBannerController extends Controller
{
    public function index(Request $request)
    {
        $data = TrainerBanner::first();

        if (!$data) {
            return response()->json(['message' => 'Trainer banner is empty']);
        }

        return response()->json(['data' => $data]);
    }
}
