<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;

class TrainerAttendanceController extends Controller
{
    public function index(Request $request){
        $user = $request->user();
        
        $data = Attendance::where('user_id', $user->id)->get();
        
        if (!$data) {
            return response()->json(['message' => 'Attedance is Empty']);
        }
        
        return response()->json(['data' => $data]);
    }
}
