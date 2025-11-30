<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\ScheduleRescheduleRequest;
use Carbon\Carbon;

class TrainerClassController extends Controller
{
    public function index(Request $request) {
        $user = $request->user();
        
        $now = Carbon::now();

        $availableclasses = Schedule::where('trainer_id', 0)
            ->where(function ($query) use ($now) {
                $query->whereNull('class_start_date')
                    ->orWhere('class_start_date', '>=', $now);
            })
            ->get()
            ->map(function ($class) {
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
        
        $now = Carbon::now();

        $data = Schedule::where('trainer_id', 0)
            ->where(function ($query) use ($now) {
                $query->whereNull('class_start_date')
                    ->orWhere('class_start_date', '>=', $now);
            })
            ->get();
        
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
        
        $class = Schedule::where('id', $class_id)
            ->where('trainer_id', $user->id)
            ->first();

        if (!$class) {
            return response()->json(['message' => 'Class not found or you are not assigned to this class'], 404);
        }

        if ((int) $class->istrainerapproved === 2) {
            return response()->json(['message' => 'This class assignment has already been rejected.'], 409);
        }

        $class = $this->releaseTrainerFromClass($class);
        
        return response()->json([
            'message' => 'Rejected successfully.',
            'data' => $class
        ]);
    }

    public function quitClass(Request $request, $classId)
    {
        $trainer = $request->user();

        $class = Schedule::where('id', $classId)
            ->where('trainer_id', $trainer->id)
            ->first();

        if (!$class) {
            return response()->json(['message' => 'Class not found or you are not assigned to this class'], 404);
        }

        if ((int) $class->istrainerapproved === 2 && (int) $class->trainer_id === 0) {
            return response()->json([
                'message' => 'This class assignment is already marked as rejected.',
                'data' => $class,
            ]);
        }

        $class = $this->releaseTrainerFromClass($class);

        return response()->json([
            'message' => 'You have successfully quit this class.',
            'data' => $class,
        ]);
    }

    public function requestReschedule(Request $request, $classId)
    {
        $trainer = $request->user();

        $schedule = Schedule::find($classId);

        if (!$schedule) {
            return response()->json(['message' => 'Class not found.'], 404);
        }

        if ((int) $schedule->trainer_id !== (int) $trainer->id) {
            return response()->json(['message' => 'You are not assigned to this class.'], 403);
        }

        $validated = $request->validate([
            'recurring_days' => 'required',
            'proposed_start_time' => 'required|date_format:H:i',
            'proposed_end_time' => 'required|date_format:H:i|after:proposed_start_time',
            'proposed_series_start_date' => 'nullable|date',
            'proposed_series_end_date' => 'nullable|date|after_or_equal:proposed_series_start_date',
            'notes' => 'nullable|string|max:500',
        ]);

        $recurringDays = $this->sanitizeRecurringDays($validated['recurring_days'] ?? []);
        if (empty($recurringDays)) {
            return response()->json(['message' => 'Please provide at least one day to reschedule.'], 422);
        }

        $pending = ScheduleRescheduleRequest::where('schedule_id', $schedule->id)
            ->where('trainer_id', $trainer->id)
            ->where('status', 0)
            ->first();

        if ($pending) {
            return response()->json(['message' => 'You already have a pending reschedule request for this class.'], 409);
        }

        $reschedule = ScheduleRescheduleRequest::create([
            'schedule_id' => $schedule->id,
            'trainer_id' => $trainer->id,
            'recurring_days' => $recurringDays,
            'proposed_start_time' => $validated['proposed_start_time'],
            'proposed_end_time' => $validated['proposed_end_time'],
            'proposed_series_start_date' => $validated['proposed_series_start_date'] ?? null,
            'proposed_series_end_date' => $validated['proposed_series_end_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 0,
        ]);

        return response()->json([
            'message' => 'Reschedule request sent for admin approval.',
            'data' => $this->serializeRescheduleRequest($reschedule),
        ], 201);
    }

    public function rescheduleRequests(Request $request, $classId)
    {
        $trainer = $request->user();

        $schedule = Schedule::find($classId);

        if (!$schedule || (int) $schedule->trainer_id !== (int) $trainer->id) {
            return response()->json(['message' => 'Class not found or you are not assigned to this class.'], 404);
        }

        $requests = ScheduleRescheduleRequest::where('schedule_id', $schedule->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return $this->serializeRescheduleRequest($item);
            });

        return response()->json([
            'class_id' => $schedule->id,
            'requests' => $requests,
        ]);
    }

    public function participants(Request $request, $classId)
    {
        $trainer = $request->user();

        $schedule = Schedule::with(['user_schedules' => function ($query) {
                $query->with(['user' => function ($userQuery) {
                    $userQuery->select([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'phone_number',
                        'profile_picture',
                    ]);
                }]);
            }])
            ->find($classId);

        if (!$schedule || (int) $schedule->trainer_id !== (int) $trainer->id) {
            return response()->json(['message' => 'Class not found or you do not have access to its participants.'], 404);
        }

        $schedule->loadCount('user_schedules');

        $participants = $schedule->user_schedules->map(function ($enrollment) {
            $user = $enrollment->user;
            $fullName = $user
                ? trim(collect([$user->first_name, $user->last_name])->filter()->implode(' '))
                : null;

            return [
                'enrollment_id' => $enrollment->id,
                'member_id' => $enrollment->user_id,
                'full_name' => $fullName ?: 'Member',
                'first_name' => $user->first_name ?? null,
                'last_name' => $user->last_name ?? null,
                'email' => $user->email ?? null,
                'phone_number' => $user->phone_number ?? $user->phone_number ?? null,
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

    private function releaseTrainerFromClass(Schedule $class): Schedule
    {
        $class->istrainerapproved = 2;
        $class->trainer_id = 0;
        $class->isadminapproved = 0;
        $class->trainer_class_start_date = null;
        $class->save();

        return $class;
    }

    private function serializeRescheduleRequest(ScheduleRescheduleRequest $request): array
    {
        $statusLabels = [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Rejected',
        ];

        return [
            'id' => $request->id,
            'schedule_id' => $request->schedule_id,
            'recurring_days' => $request->recurring_days ?? [],
            'proposed_start_time' => $request->proposed_start_time,
            'proposed_end_time' => $request->proposed_end_time,
            'proposed_series_start_date' => $request->proposed_series_start_date
                ? $request->proposed_series_start_date->toDateString()
                : null,
            'proposed_series_end_date' => $request->proposed_series_end_date
                ? $request->proposed_series_end_date->toDateString()
                : null,
            'notes' => $request->notes,
            'status' => (int) $request->status,
            'status_label' => $statusLabels[(int) $request->status] ?? 'Pending',
            'responded_at' => $request->responded_at ? Carbon::parse($request->responded_at)->toIso8601String() : null,
            'created_at' => $request->created_at ? $request->created_at->toIso8601String() : null,
        ];
    }

    private function sanitizeRecurringDays($raw): array
    {
        $daysMeta = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : explode(',', $raw);
        }

        if (!is_array($raw)) {
            return [];
        }

        $raw = array_map(function ($day) {
            return strtolower(trim($day));
        }, $raw);

        $filtered = array_values(array_intersect($daysMeta, $raw));

        return array_values(array_unique($filtered));
    }
}
