<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\ClassAttendance;
use App\Models\Schedule;
use App\Models\UserSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TrainerClassAttendanceController extends Controller
{
    public function index(Request $request, $classId)
    {
        $trainer = $request->user();
        $schedule = Schedule::find($classId);

        if (! $schedule || (int) $schedule->trainer_id !== (int) $trainer->id) {
            return response()->json(['message' => 'Class not found or you do not have access to its attendance.'], 404);
        }

        $query = ClassAttendance::with(['user'])
            ->where('schedule_id', $schedule->id);

        if ($request->filled('session_date')) {
            try {
                $sessionDate = Carbon::parse($request->session_date)->toDateString();
                $query->whereDate('session_date', $sessionDate);
            } catch (\Throwable $th) {
                return response()->json(['message' => 'Invalid session date.'], 422);
            }
        }

        $attendances = $query
            ->orderByDesc('attended_at')
            ->get()
            ->map(fn ($attendance) => $this->serializeAttendance($attendance))
            ->values();

        return response()->json(['data' => $attendances]);
    }

    public function store(Request $request, $classId)
    {
        $trainer = $request->user();
        $schedule = Schedule::find($classId);

        if (! $schedule || (int) $schedule->trainer_id !== (int) $trainer->id) {
            return response()->json(['message' => 'Class not found or you do not have access to its attendance.'], 404);
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'session_date' => 'required|date',
            'source' => 'nullable|string|max:50',
            'attended_at' => 'nullable|date',
        ]);

        $isEnrolled = UserSchedule::where('schedule_id', $schedule->id)
            ->where('user_id', $data['user_id'])
            ->exists();

        if (! $isEnrolled) {
            return response()->json(['message' => 'Member is not enrolled in this class.'], 422);
        }

        $sessionDate = Carbon::parse($data['session_date'])->toDateString();
        $attendedAt = isset($data['attended_at'])
            ? Carbon::parse($data['attended_at'])
            : now();

        $attendance = ClassAttendance::updateOrCreate(
            [
                'schedule_id' => $schedule->id,
                'user_id' => $data['user_id'],
                'session_date' => $sessionDate,
            ],
            [
                'marked_by' => $trainer->id,
                'attended_at' => $attendedAt,
                'source' => $data['source'] ?? 'manual',
            ]
        );

        return response()->json(['data' => $this->serializeAttendance($attendance)]);
    }

    public function destroy(Request $request, $classId)
    {
        $trainer = $request->user();
        $schedule = Schedule::find($classId);

        if (! $schedule || (int) $schedule->trainer_id !== (int) $trainer->id) {
            return response()->json(['message' => 'Class not found or you do not have access to its attendance.'], 404);
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'session_date' => 'required|date',
        ]);

        $sessionDate = Carbon::parse($data['session_date'])->toDateString();

        $attendance = ClassAttendance::where('schedule_id', $schedule->id)
            ->where('user_id', $data['user_id'])
            ->whereDate('session_date', $sessionDate)
            ->first();

        if (! $attendance) {
            return response()->json(['message' => 'Attendance record not found.'], 404);
        }

        $attendance->delete();

        return response()->json(['message' => 'Attendance removed.']);
    }

    private function serializeAttendance(ClassAttendance $attendance): array
    {
        $user = $attendance->user;
        $fullName = $user
            ? trim(collect([$user->first_name, $user->last_name])->filter()->implode(' '))
            : null;

        return [
            'id' => $attendance->id,
            'schedule_id' => $attendance->schedule_id,
            'user_id' => $attendance->user_id,
            'session_date' => $attendance->session_date?->toDateString(),
            'attended_at' => optional($attendance->attended_at)->toIso8601String(),
            'source' => $attendance->source,
            'full_name' => $fullName,
            'email' => $user->email ?? null,
            'phone_number' => $user->phone_number ?? null,
        ];
    }
}
