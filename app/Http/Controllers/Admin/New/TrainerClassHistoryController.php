<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TrainerClassHistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search'     => 'nullable|string|max:255',
            'trainer_id' => 'nullable|exists:users,id',
            'status'     => 'nullable|in:all,pending,approved,rejected',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $filters = [
            'search'     => trim((string) $request->input('search', '')),
            'trainer_id' => $request->input('trainer_id'),
            'status'     => $request->input('status', 'all'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
        ];

        $statusMap = [
            'pending'  => 0,
            'approved' => 1,
            'rejected' => 2,
        ];

        if (!array_key_exists($filters['status'], $statusMap)) {
            $filters['status'] = 'all';
        }

        $now = Carbon::now();

        $baseQuery = Schedule::with(['user', 'user_schedules'])
            ->withCount('user_schedules')
            ->whereNotNull('class_end_date')
            ->where('class_end_date', '<', $now);

        if ($filters['trainer_id']) {
            $baseQuery->where('trainer_id', $filters['trainer_id']);
        }

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';

            $baseQuery->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('class_code', 'like', $like)
                    ->orWhereHas('user', function ($trainerQuery) use ($like) {
                        $trainerQuery->where(function ($nameQuery) use ($like) {
                            $nameQuery->whereRaw(
                                "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?",
                                [$like]
                            )->orWhere('first_name', 'like', $like)
                             ->orWhere('last_name', 'like', $like);
                        });
                    });
            });
        }

        if ($filters['start_date']) {
            $baseQuery->whereDate('class_end_date', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $baseQuery->whereDate('class_end_date', '<=', $filters['end_date']);
        }

        $statusTallies = [
            'all'      => (clone $baseQuery)->count(),
            'pending'  => (clone $baseQuery)->where('isadminapproved', $statusMap['pending'])->count(),
            'approved' => (clone $baseQuery)->where('isadminapproved', $statusMap['approved'])->count(),
            'rejected' => (clone $baseQuery)->where('isadminapproved', $statusMap['rejected'])->count(),
        ];

        $historyQuery = clone $baseQuery;
        if ($filters['status'] !== 'all') {
            $historyQuery->where('isadminapproved', $statusMap[$filters['status']]);
        }

        $queryParams = $request->query();

        $classes = (clone $historyQuery)
            ->orderByDesc('class_end_date')
            ->paginate(10)
            ->appends($queryParams);

        $statsBase = clone $historyQuery;
        $stats = [
            'classes'     => (clone $statsBase)->count(),
            'trainers'    => (clone $statsBase)->where('trainer_id', '>', 0)->distinct('trainer_id')->count('trainer_id'),
            'enrollments' => (clone $statsBase)->get()->sum('user_schedules_count'),
        ];

        $trainerOptions = User::where('role_id', 5)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('admin.history.trainer-classes', [
            'classes'        => $classes,
            'filters'        => $filters,
            'statusTallies'  => $statusTallies,
            'trainerOptions' => $trainerOptions,
            'stats'          => $stats,
        ]);
    }
}
