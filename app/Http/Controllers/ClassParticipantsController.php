<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use Carbon\Carbon;

class ClassParticipantsController extends Controller
{
    public function show(Request $request, $classId)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $schedule = Schedule::with(['activeUserSchedules' => function ($query) {
                $query->with(['user' => function ($userQuery) {
                    $userQuery->select([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'profile_picture',
                    ]);
                }])->whereHas('user', function ($userQuery) {
                    $userQuery->where('is_archive', 0);
                });
            }])
            ->find($classId);

        if (! $schedule || (int) ($schedule->is_archieve ?? 0) === 1 || (int) ($schedule->isadminapproved ?? 0) !== 1) {
            return response()->json(['message' => 'Class not found.'], 404);
        }

        $isTrainer = (int) ($user->role_id ?? 0) === 5;
        $isMember = (int) ($user->role_id ?? 0) === 3;

        if ($isTrainer) {
            if ((int) $schedule->trainer_id !== (int) $user->id) {
                return response()->json(['message' => 'You are not assigned to this class.'], 403);
            }
        } elseif ($isMember) {
            $isEnrolled = $schedule->activeUserSchedules->contains(function ($enrollment) use ($user) {
                return (int) $enrollment->user_id === (int) $user->id;
            });

            if (! $isEnrolled) {
                return response()->json(['message' => 'Join the class to view its participants.'], 403);
            }
        } else {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $schedule->loadCount(['activeUserSchedules as user_schedules_count']);

        $participants = $schedule->activeUserSchedules->map(function ($enrollment) {
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
}
