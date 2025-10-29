<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;

class TrainerClassController extends Controller
{
    public function index(Request $request) {
        $user = $request->user();
        
        $availableclasses = Schedule::where('trainer_id', 0)->get()->map(function ($class) {
            $class->trainer = 'No Trainer';
            $class->type = 'availableclasses';
            return $class;
        });
        
        $myclasses = Schedule::where('trainer_id', $user->id)
            ->where('istrainerapproved', '!=', 0)
            ->get()
            ->map(function ($class) {
                $class->trainer = ($class->trainer_id == 0) ? 'No Trainer' : ($class->user->first_name . ' ' . $class->user->last_name);
                $class->type = 'myclasses';
                return $class;
            });
        
        $classesassignbyadmin = Schedule::where('trainer_id', $user->id)
            ->where('istrainerapproved', 0)
            ->get()
            ->map(function ($class) {
                $class->trainer = ($class->trainer_id == 0) ? 'No Trainer' : ($class->user->first_name . ' ' . $class->user->last_name);
                $class->type = 'classesassignbyadmin';
                return $class;
            });
        
        return response()->json([
            'availableclasses' => $availableclasses,
            'myclasses' => $myclasses,
            'classesassignbyadmin' => $classesassignbyadmin
        ]);
    }

    public function availableclasses(){
        
        $data = Schedule::where('trainer_id', 0)->get();
        
        if (!$data) {
            return response()->json(['message' => 'Class is Empty']);
        }
        
        return response()->json(['data' => $data]);
    }
    
    public function myclasses(Request $request){
        $user = $request->user();
        $data = Schedule::where('trainer_id', $user->id)->where('istrainerapproved', '!=', 0)->get();
        
        if (!$data) {
            return response()->json(['message' => 'Class is Empty']);
        }

        return response()->json(['data' => $data]);
    }

    public function myclassesbyadmin(Request $request){
        $user = $request->user();
        $data = Schedule::where('trainer_id', $user->id)->where('istrainerapproved', 0)->get();
        $dataCount = Schedule::where('trainer_id', $user->id)->where('istrainerapproved', 0)->count();
        
        if (!$data) {
            return response()->json(['message' => 'Class is Empty']);
        }

        return response()->json(['data' => $data, 'count' => $dataCount]);
    }

    public function applyavailableclass(Request $request){
        $request->validate([
            'class_id' => 'required|exists:schedules,id',
            'trainer_class_start_date' => 'nullable',
        ]);
        
        $user = $request->user();
        $class_id = $request->class_id;
        $trainer_class_start_date = $request->trainer_class_start_date;
        
        $class = Schedule::find($class_id);

        if (!$class) {
            return response()->json(['message' => 'Class not found or Someone trainer already applied']);
        }

        $currentDate = now();
        if ($currentDate > $class->class_start_date || $currentDate > $class->class_end_date) {
            return response()->json([
                'message' => "You can't apply within the class schedule period"
            ], 422);
        }

        $class->trainer_id = $user->id;
        $class->istrainerapproved = 1;
        $class->trainer_class_start_date = $trainer_class_start_date;
        $class->save();
        
        return response()->json([
            'message' => 'Apply successfully. Your class is pending approval.',
            'data' => $class
        ]);
    }
    
    public function trainerapproveclass(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:schedules,id',
        ]);
        
        $user = $request->user();
        $class_id = $request->class_id;
        
        $class = Schedule::where('isadminapproved', 1)->where('istrainerapproved', 0)->find($class_id);
        
        if (!$class) {
            return response()->json(['message' => 'Class not found or Trainer is already approved']);
        }
        
        $class->istrainerapproved = 1;
        $class->save();
        
        return response()->json([
            'message' => 'Approved successfully.',
            'data' => $class
        ]);
    }
    
    public function trainerrejectclass(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:schedules,id',
        ]);
        
        $user = $request->user();
        $class_id = $request->class_id;
        
        $class = Schedule::where('isadminapproved', 1)->where('istrainerapproved', 0)->find($class_id);
        
        if (!$class) {
            return response()->json(['message' => 'Class not found or Trainer is already rejected']);
        }
        
        $class->istrainerapproved = 2;
        $class->trainer_id = 0;
        $class->isadminapproved = 0;
        $class->save();
        
        return response()->json([
            'message' => 'Rejected successfully.',
            'data' => $class
        ]);
    }
}
