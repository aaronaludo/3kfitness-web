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
            ->where('is_archieve', 0)
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

                if ((int) ($schedule->is_archieve ?? 0) === 1) {
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

    public function participants(Request $request, $classId)
    {
        $user = $request->user();

        $schedule = Schedule::with(['user_schedules' => function ($query) {
                $query->with(['user' => function ($userQuery) {
                    $userQuery->select([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'profile_picture',
                    ]);
                }]);
            }])
            ->find($classId);

        if (!$schedule || (int) ($schedule->is_archieve ?? 0) === 1 || (int) ($schedule->isadminapproved ?? 0) !== 1) {
            return response()->json(['message' => 'Class not found.'], 404);
        }

        $isEnrolled = $schedule->user_schedules->contains(function ($enrollment) use ($user) {
            return (int) $enrollment->user_id === (int) $user->id;
        });

        if (!$isEnrolled) {
            return response()->json(['message' => 'Join the class to view its participants.'], 403);
        }

        $schedule->loadCount('user_schedules');

        $participants = $schedule->user_schedules->map(function ($enrollment) {
            $member = $enrollment->user;
            $fullName = $member
                ? trim(collect([optional($member)->first_name, optional($member)->last_name])->filter()->implode(' '))
                : null;

            return [
                'enrollment_id' => $enrollment->id,
                'member_id' => $enrollment->user_id,
                'full_name' => $fullName ?: 'Member',
                'first_name' => optional($member)->first_name,
                'last_name' => optional($member)->last_name,
                'email' => optional($member)->email,
                'phone_number' => optional($member)->phone_number,
                'joined_at' => $enrollment->created_at ? $enrollment->created_at->toIso8601String() : null,
            ];
        })->values();

        $slots = $schedule->slots;
        $enrolledCount = $participants->count();
        $availableSlots = is_null($slots) ? null : max($slots - $enrolledCount, 0);

        $startDate = $this->normalizeDateTime($schedule->class_start_date);
        $endDate = $this->normalizeDateTime($schedule->class_end_date);

        return response()->json([
            'class' => [
                'id' => $schedule->id,
                'name' => $schedule->name,
                'class_code' => $schedule->class_code,
                'class_start_date' => $startDate,
                'class_end_date' => $endDate,
                'slots' => $slots,
                'enrolled_count' => $enrolledCount,
                'available_slots' => $availableSlots,
                'isadminapproved' => (int) $schedule->isadminapproved,
                'istrainerapproved' => (int) $schedule->istrainerapproved,
            ],
            'participants' => $participants,
        ]);
    }

    public function enrollmentHistory(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();

        $enrollments = UserSchedule::with(['schedule.user'])
            ->where('user_id', $user->id)
            ->whereHas('schedule', function ($query) use ($now) {
                $query->whereNotNull('class_end_date')
                    ->where('class_end_date', '<', $now);
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($enrollment) use ($now) {
                $schedule = $enrollment->schedule;

                if (!$schedule) {
                    return null;
                }

                $start = $schedule->class_start_date ? Carbon::parse($schedule->class_start_date) : null;
                $end = $schedule->class_end_date ? Carbon::parse($schedule->class_end_date) : null;

                $status = 'upcoming';

                if ($end && $now->gt($end)) {
                    $status = 'completed';
                } elseif ($start && $now->gte($start)) {
                    $status = 'active';
                }

                $trainerName = 'Not assigned';

                if ((int) $schedule->trainer_id === 0) {
                    $trainerName = 'No trainer assigned yet';
                } elseif ($schedule->user) {
                    $trainerName = trim(collect([$schedule->user->first_name, $schedule->user->last_name])->filter()->implode(' '));
                }

                return [
                    'id' => $enrollment->id,
                    'schedule_id' => $schedule->id,
                    'class_name' => $schedule->name,
                    'class_code' => $schedule->class_code,
                    'trainer_name' => $trainerName,
                    'class_start_date' => $start ? $start->toIso8601String() : null,
                    'class_end_date' => $end ? $end->toIso8601String() : null,
                    'joined_at' => $enrollment->created_at ? $enrollment->created_at->toIso8601String() : null,
                    'status' => $status,
                ];
            })
            ->filter()
            ->values();

        $statusCounts = [
            'total'     => $enrollments->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'active'    => $enrollments->where('status', 'active')->count(),
            'upcoming'  => $enrollments->where('status', 'upcoming')->count(),
            'unknown'   => $enrollments->where('status', 'unknown')->count(),
        ];

        return response()->json([
            'data'   => $enrollments,
            'counts' => $statusCounts,
        ]);
    }

    private function normalizeDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (\Throwable $th) {
            return null;
        }
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
            'series_start_date' => $this->normalizeDateTime($schedule->series_start_date),
            'series_end_date' => $this->normalizeDateTime($schedule->series_end_date),
            'class_start_time' => $schedule->class_start_time,
            'class_end_time' => $schedule->class_end_time,
            'recurring_days' => $schedule->recurring_days,
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
