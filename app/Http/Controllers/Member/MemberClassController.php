<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\UserSchedule;

class MemberClassController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $now = now();

        $availableclasses = Schedule::withCount('user_schedules')->get();

        $filteredavailableclasses = $availableclasses->filter(function ($class) use ($now) {
            return $class->user_schedules_count < $class->slots 
                && $class->class_start_date >= $now
                && $class->isadminapproved == 1
                && !$class->user_schedules->isNotEmpty();
        })->values()->map(function ($class) {
            $class->trainer = ($class->trainer_id == 0) ? 'No Trainer' : ($class->user->first_name . ' ' . $class->user->last_name);
            $class->type = 'availableclasses';
            return $class;
        });

        $myclasses = UserSchedule::where('user_id', $user->id)
            ->with('schedule')
            ->get()
            ->filter(function ($class) use ($now) {
                return optional($class->schedule)->class_start_date >= $now
                    && optional($class->schedule)->isadminapproved == 1;
            })
            ->values()
            ->map(function ($class) {
                $class->schedule->trainer = ($class->schedule->trainer_id == 0) ? 'No Trainer' : ($class->schedule->user->first_name . ' ' . $class->schedule->user->last_name);
                $class->schedule->type = 'myclasses';
                return $class;
            });

        return response()->json([
            'myclasses' => $myclasses,
            'availableclasses' => $filteredavailableclasses
        ]);
    }

    public function joinclass(Request $request){
        $request->validate([
            'class_id' => 'required',
        ]);

        $user = $request->user();

        $schedule = Schedule::findOrFail($request->class_id);
        $userschedule_count = UserSchedule::where('schedule_id', $request->class_id)->count();
        $userschedule_user_validation = UserSchedule::where('schedule_id', $request->class_id)->where('user_id', $user->id)->first();
        
        if($userschedule_user_validation){
            return response()->json(['message' => 'You need to pick other class because you already joined.']);
        }

        if ($userschedule_count >= $schedule->slots) {
            return response()->json(['message' => 'Class is already full. Please choose another class.'], 400);
        }

        $data = new UserSchedule;
        $data->user_id = $user->id;
        $data->schedule_id = $request->class_id;
        $data->save();

        return response()->json(['message' => 'Join Class successfully.']);
    }

    public function leaveclass(Request $request){
        $request->validate([
            'class_id' => 'required',
        ]);

        $user = $request->user();
        $data = UserSchedule::where('user_id', $user->id)
            ->where('schedule_id', $request->class_id)
            ->first();

        if (!$data) {
            return response()->json(['message' => 'You are not enrolled in this class.'], 400);
        }

        $data->delete();

        return response()->json(['message' => 'Leave Class successfully.']);
    }
}
