<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\ScheduleRescheduleRequest;
use App\Models\User;
use Carbon\Carbon;
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
            $this->applyRescheduleHistorySearch($baseQuery, $filters['search']);
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

    protected function applyRescheduleHistorySearch($query, string $search)
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $like = '%' . $search . '%';
        $lowerSearch = strtolower($search);
        $integerSearch = ctype_digit($search) ? (int) $search : null;
        $activeEnrollmentCountSql = "(select count(*) from user_schedules inner join users on users.id = user_schedules.user_id and users.is_archive = 0 where user_schedules.schedule_id = schedule_reschedule_requests.schedule_id)";

        $dayCodes = [];
        $dayMap = [
            'sun' => ['sun', 'sunday'],
            'mon' => ['mon', 'monday'],
            'tue' => ['tue', 'tuesday'],
            'wed' => ['wed', 'wednesday'],
            'thu' => ['thu', 'thursday'],
            'fri' => ['fri', 'friday'],
            'sat' => ['sat', 'saturday'],
        ];

        foreach ($dayMap as $code => $aliases) {
            foreach ($aliases as $alias) {
                if (strpos($lowerSearch, $alias) !== false) {
                    $dayCodes[] = $code;
                    break;
                }
            }
        }

        $dayCodes = array_values(array_unique($dayCodes));

        $parsedDate = null;
        try {
            $parsedDate = Carbon::parse($search)->toDateString();
        } catch (\Exception $e) {
            $parsedDate = null;
        }

        return $query->where(function ($query) use (
            $like,
            $lowerSearch,
            $integerSearch,
            $activeEnrollmentCountSql,
            $dayCodes,
            $parsedDate
        ) {
            if ($integerSearch !== null) {
                $query->orWhere('id', $integerSearch)
                    ->orWhere('schedule_id', $integerSearch)
                    ->orWhere('trainer_id', $integerSearch)
                    ->orWhere('responded_by', $integerSearch)
                    ->orWhereRaw("{$activeEnrollmentCountSql} = ?", [$integerSearch]);
            }

            $query->orWhere('notes', 'like', $like)
                ->orWhere('admin_comment', 'like', $like)
                ->orWhere('proposed_start_time', 'like', $like)
                ->orWhere('proposed_end_time', 'like', $like)
                ->orWhere('proposed_series_start_date', 'like', $like)
                ->orWhere('proposed_series_end_date', 'like', $like)
                ->orWhere('target_session_dates', 'like', $like)
                ->orWhere('proposed_session_dates', 'like', $like)
                ->orWhere('recurring_days', 'like', $like)
                ->orWhere('created_at', 'like', $like)
                ->orWhere('responded_at', 'like', $like);

            foreach ($dayCodes as $code) {
                $query->orWhereJsonContains('recurring_days', $code);
            }

            if ($parsedDate) {
                $query->orWhereDate('proposed_series_start_date', $parsedDate)
                    ->orWhereDate('proposed_series_end_date', $parsedDate)
                    ->orWhereDate('created_at', $parsedDate)
                    ->orWhereDate('responded_at', $parsedDate);
            }

            $query->orWhereHas('schedule', function ($scheduleQuery) use ($like) {
                $scheduleQuery
                    ->where('name', 'like', $like)
                    ->orWhere('class_code', 'like', $like);
            });

            $query->orWhereHas('trainer', function ($trainerQuery) use ($like) {
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
            });

            $query->orWhereHas('responder', function ($responderQuery) use ($like) {
                $responderQuery->where(function ($nameQuery) use ($like) {
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
            });

            if (strpos($lowerSearch, 'pending') !== false) {
                $query->orWhere('status', 0);
            }
            if (strpos($lowerSearch, 'approved') !== false) {
                $query->orWhere('status', 1);
            }
            if (strpos($lowerSearch, 'rejected') !== false) {
                $query->orWhere('status', 2);
            }
            if (strpos($lowerSearch, 'resolved') !== false) {
                $query->orWhereIn('status', [1, 2]);
            }
        });
    }
}
