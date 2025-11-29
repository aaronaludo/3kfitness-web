<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use App\Models\Attendance2;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceHistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search'        => 'nullable|string|max:255',
            'role_id'       => 'nullable|exists:roles,id',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'status'        => 'nullable|in:all,open,completed',
            'show_archived' => 'nullable|boolean',
        ]);

        $filters = [
            'search'        => trim((string) $request->input('search', '')),
            'role_id'       => $request->input('role_id'),
            'start_date'    => $request->input('start_date'),
            'end_date'      => $request->input('end_date'),
            'status'        => $request->input('status', 'completed'),
            'show_archived' => $request->boolean('show_archived'),
        ];

        $validStatuses = ['all', 'open', 'completed'];
        if (!in_array($filters['status'], $validStatuses, true)) {
            $filters['status'] = 'completed';
        }

        $baseQuery = Attendance2::with(['user.role'])
            ->where('is_archive', $filters['show_archived'] ? 1 : 0);

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $baseQuery->where(function ($query) use ($like) {
                $query->whereHas('user', function ($userQuery) use ($like) {
                    $userQuery->where(function ($nameQuery) use ($like) {
                        $nameQuery->whereRaw(
                            "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                            [$like]
                        )->orWhere('first_name', 'like', $like)
                         ->orWhere('last_name', 'like', $like)
                         ->orWhere('email', 'like', $like)
                         ->orWhere('phone_number', 'like', $like);
                    });
                });
            });
        }

        if ($filters['role_id']) {
            $baseQuery->whereHas('user', function ($query) use ($filters) {
                $query->where('role_id', $filters['role_id']);
            });
        }

        if ($filters['start_date']) {
            $baseQuery->whereDate('clockin_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $baseQuery->whereDate('clockin_at', '<=', $filters['end_date']);
        }

        $statusTallies = [
            'all'       => (clone $baseQuery)->count(),
            'open'      => (clone $baseQuery)->whereNull('clockout_at')->count(),
            'completed' => (clone $baseQuery)->whereNotNull('clockout_at')->count(),
        ];

        $historyQuery = clone $baseQuery;
        if ($filters['status'] === 'open') {
            $historyQuery->whereNull('clockout_at');
        } elseif ($filters['status'] === 'completed') {
            $historyQuery->whereNotNull('clockout_at');
        }

        $queryParams = $request->query();

        $attendances = (clone $historyQuery)
            ->orderByDesc('clockin_at')
            ->paginate(10)
            ->appends($queryParams);

        $statsBase = clone $historyQuery;
        $stats = [
            'records'   => (clone $statsBase)->count(),
            'people'    => (clone $statsBase)->distinct('user_id')->count('user_id'),
            'completed' => (clone $statsBase)->whereNotNull('clockout_at')->count(),
        ];

        $roleOptions = Role::orderBy('name')->get(['id', 'name']);

        return view('admin.history.attendances', [
            'attendances'  => $attendances,
            'filters'      => $filters,
            'statusTallies'=> $statusTallies,
            'roleOptions'  => $roleOptions,
            'stats'        => $stats,
        ]);
    }
}
