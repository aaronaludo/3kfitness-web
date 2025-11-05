<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\UserSchedule;
use App\Traits\ResolvesActiveMembership;
use Carbon\Carbon;

class MemberClassController extends Controller
{
    use ResolvesActiveMembership;

    public function index(Request $request)
    {
        $user = $request->user();
        $now = now();

        $availableClasses = Schedule::with(['user'])
            ->withCount('user_schedules')
            ->where('isadminapproved', 1)
            ->where(function ($query) use ($now) {
                $query->whereNull('class_start_date')->orWhere('class_start_date', '>=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('class_end_date')->orWhere('class_end_date', '>=', $now);
            })
            ->whereDoesntHave('user_schedules', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get()
            ->filter(function ($class) {
                if ($class->slots === null) {
                    return true;
                }

                return $class->user_schedules_count < $class->slots;
            })
            ->values();

        $myclasses = UserSchedule::where('user_id', $user->id)
            ->with(['schedule' => function ($query) {
                $query->with(['user'])
                    ->withCount('user_schedules');
            }])
            ->get()
            ->filter(function ($class) use ($now) {
                $schedule = $class->schedule;
                if (!$schedule) {
                    return false;
                }

                if ((int) $schedule->isadminapproved !== 1) {
                    return false;
                }

                if ($schedule->class_start_date === null) {
                    return true;
                }

                return Carbon::parse($schedule->class_start_date)->greaterThanOrEqualTo($now);
            })
            ->values();

        $formattedAvailableClasses = $availableClasses->map(function ($class) use ($now) {
            return $this->transformSchedule($class, 'availableclasses', false, $now);
        });

        $formattedMyClasses = $myclasses->map(function ($class) use ($now) {
            $schedule = $class->schedule;
            if (!$schedule) {
                return null;
            }

            return $this->transformSchedule($schedule, 'myclasses', true, $now);
        })->filter()->values();

        return response()->json([
            'myclasses' => $formattedMyClasses,
            'availableclasses' => $formattedAvailableClasses,
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

        $activeMembership = $this->resolveActiveMembershipForUser($user);

        $classLimit = optional(optional($activeMembership)->membership)->class_limit_per_month;

        if (!is_null($classLimit) && $classLimit > 0) {
            $startOfMonth = now()->copy()->startOfMonth();
            $endOfMonth = now()->copy()->endOfMonth();

            $monthlyJoinCount = UserSchedule::where('user_id', $user->id)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            if ($monthlyJoinCount >= $classLimit) {
                return response()->json([
                    'message' => 'You have reached your membership class limit for this month.'
                ], 400);
            }
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

    protected function transformSchedule(Schedule $schedule, string $type, bool $isJoined, Carbon $now): array
    {
        $startDate = $schedule->class_start_date ? Carbon::parse($schedule->class_start_date) : null;
        $endDate = $schedule->class_end_date ? Carbon::parse($schedule->class_end_date) : null;

        $status = 'unknown';
        if ($startDate && $now->lt($startDate)) {
            $status = 'upcoming';
        } elseif ($startDate && $endDate && $now->between($startDate, $endDate)) {
            $status = 'active';
        } elseif ($endDate && $now->gt($endDate)) {
            $status = 'completed';
        }

        $joinedCount = $schedule->user_schedules_count ?? $schedule->user_schedules()->count();
        $availableSlots = null;
        if ($schedule->slots !== null) {
            $availableSlots = max($schedule->slots - $joinedCount, 0);
        }

        $trainerName = 'Trainer details pending';
        if ((int) $schedule->trainer_id === 0) {
            $trainerName = 'No trainer assigned yet';
        } elseif ($schedule->user) {
            $trainerName = trim(collect([
                $schedule->user->first_name ?? null,
                $schedule->user->last_name ?? null,
            ])->filter()->implode(' '));
        }

        return [
            'id' => $schedule->id,
            'name' => $schedule->name,
            'description' => $schedule->description,
            'class_code' => $schedule->class_code,
            'class_start_date' => $startDate ? $startDate->toIso8601String() : null,
            'class_end_date' => $endDate ? $endDate->toIso8601String() : null,
            'class_status' => $status,
            'slots' => $schedule->slots,
            'user_schedules_count' => $joinedCount,
            'available_slots' => $availableSlots,
            'trainer_id' => $schedule->trainer_id,
            'trainer_name' => $trainerName,
            'trainer' => $trainerName,
            'type' => $type,
            'is_joined' => $isJoined,
            'isadminapproved' => (int) $schedule->isadminapproved,
            'istrainerapproved' => (int) $schedule->istrainerapproved,
            'rejection_reason' => $schedule->rejection_reason,
            'image' => $schedule->image,
            'image_url' => $schedule->image ? url($schedule->image) : null,
            'created_at' => $schedule->created_at ? $schedule->created_at->toIso8601String() : null,
            'updated_at' => $schedule->updated_at ? $schedule->updated_at->toIso8601String() : null,
        ];
    }
}
