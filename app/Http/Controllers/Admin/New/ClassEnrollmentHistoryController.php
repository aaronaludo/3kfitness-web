<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\UserSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClassEnrollmentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search'     => 'nullable|string|max:255',
            'class_id'   => 'nullable|exists:schedules,id',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $filters = [
            'search'     => trim((string) $request->input('search', '')),
            'class_id'   => $request->input('class_id'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        $now = Carbon::now();

        $baseQuery = UserSchedule::with(['user.role', 'schedule.user'])
            ->whereHas('schedule', function ($query) use ($now) {
                $query->whereNotNull('class_end_date')
                    ->where('class_end_date', '<', $now);
            });

        if ($filters['class_id']) {
            $baseQuery->where('schedule_id', $filters['class_id']);
        }

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';

            $baseQuery->where(function ($query) use ($like) {
                $query
                    ->whereHas('user', function ($userQuery) use ($like) {
                        $userQuery->where(function ($nameQuery) use ($like) {
                            $nameQuery->whereRaw(
                                "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                                [$like]
                            )
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone_number', 'like', $like);
                        });
                    })
                    ->orWhereHas('schedule', function ($scheduleQuery) use ($like) {
                        $scheduleQuery
                            ->where('name', 'like', $like)
                            ->orWhere('class_code', 'like', $like);
                    });
            });
        }

        if ($filters['start_date']) {
            $baseQuery->whereDate('created_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $baseQuery->whereDate('created_at', '<=', $filters['end_date']);
        }

        $queryParams = $request->query();

        $enrollments = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->paginate(10)
            ->appends($queryParams);

        $stats = [
            'total'   => (clone $baseQuery)->count(),
            'members' => (clone $baseQuery)->distinct('user_id')->count('user_id'),
            'classes' => (clone $baseQuery)->distinct('schedule_id')->count('schedule_id'),
        ];

        $classOptions = Schedule::whereNotNull('class_end_date')
            ->where('class_end_date', '<', $now)
            ->orderBy('name')
            ->get(['id', 'name', 'class_code']);

        return view('admin.history.enrollments', [
            'enrollments'  => $enrollments,
            'classOptions' => $classOptions,
            'filters'      => $filters,
            'stats'        => $stats,
        ]);
    }
}
