<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\ScheduleRescheduleRequest;
use App\Models\User;
use Illuminate\Http\Request;

class RescheduleRequestHistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:all,resolved,pending,approved,rejected',
            'trainer_id' => 'nullable|exists:users,id',
            'class_id' => 'nullable|exists:schedules,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => $request->input('status', 'resolved'),
            'trainer_id' => $request->input('trainer_id'),
            'class_id' => $request->input('class_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        $statusMap = [
            'pending' => 0,
            'approved' => 1,
            'rejected' => 2,
            'resolved' => [1, 2],
        ];

        $baseQuery = ScheduleRescheduleRequest::with(['schedule.user', 'trainer', 'responder']);

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $baseQuery->where(function ($query) use ($like) {
                $query
                    ->whereHas('schedule', function ($scheduleQuery) use ($like) {
                        $scheduleQuery
                            ->where('name', 'like', $like)
                            ->orWhere('class_code', 'like', $like);
                    })
                    ->orWhereHas('trainer', function ($trainerQuery) use ($like) {
                        $trainerQuery->where(function ($nameQuery) use ($like) {
                            $nameQuery
                                ->whereRaw(
                                    "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                                    [$like]
                                )
                                ->orWhere('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('user_code', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                    })
                    ->orWhereHas('responder', function ($responderQuery) use ($like) {
                        $responderQuery->where(function ($nameQuery) use ($like) {
                            $nameQuery
                                ->whereRaw(
                                    "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                                    [$like]
                                )
                                ->orWhere('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like);
                        });
                    })
                    ->orWhere('notes', 'like', $like)
                    ->orWhere('admin_comment', 'like', $like);
            });
        }

        if ($filters['trainer_id']) {
            $baseQuery->where('trainer_id', $filters['trainer_id']);
        }

        if ($filters['class_id']) {
            $baseQuery->where('schedule_id', $filters['class_id']);
        }

        if ($filters['start_date']) {
            $baseQuery->whereDate('created_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $baseQuery->whereDate('created_at', '<=', $filters['end_date']);
        }

        $statusTallies = [
            'all' => (clone $baseQuery)->count(),
            'resolved' => (clone $baseQuery)->whereIn('status', $statusMap['resolved'])->count(),
            'pending' => (clone $baseQuery)->where('status', $statusMap['pending'])->count(),
            'approved' => (clone $baseQuery)->where('status', $statusMap['approved'])->count(),
            'rejected' => (clone $baseQuery)->where('status', $statusMap['rejected'])->count(),
        ];

        $historyQuery = clone $baseQuery;
        $statusFilter = $filters['status'];

        if ($statusFilter !== 'all') {
            if ($statusFilter === 'resolved') {
                $historyQuery->whereIn('status', $statusMap['resolved']);
            } elseif (array_key_exists($statusFilter, $statusMap)) {
                $historyQuery->where('status', $statusMap[$statusFilter]);
            }
        }

        $rescheduleRequests = (clone $historyQuery)
            ->orderByDesc('created_at')
            ->paginate(10)
            ->appends($request->query());

        $statsBase = clone $historyQuery;
        $stats = [
            'total' => (clone $statsBase)->count(),
            'approved' => (clone $statsBase)->where('status', $statusMap['approved'])->count(),
            'rejected' => (clone $statsBase)->where('status', $statusMap['rejected'])->count(),
        ];

        $classOptions = Schedule::orderBy('name')
            ->orderBy('class_code')
            ->get(['id', 'name', 'class_code']);

        $trainerOptions = User::where('role_id', 5)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('admin.history.reschedule-requests', [
            'rescheduleRequests' => $rescheduleRequests,
            'filters' => $filters,
            'statusTallies' => $statusTallies,
            'classOptions' => $classOptions,
            'trainerOptions' => $trainerOptions,
            'stats' => $stats,
        ]);
    }
}
