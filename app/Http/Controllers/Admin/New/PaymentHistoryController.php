<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search'        => 'nullable|string|max:255',
            'membership_id' => 'nullable|exists:memberships,id',
            'status'        => 'nullable|in:all,pending,approved,rejected',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'show_archived' => 'nullable|boolean',
        ]);

        $filters = [
            'search'        => trim((string) $request->input('search', '')),
            'membership_id' => $request->input('membership_id'),
            'status'        => $request->input('status', 'all'),
            'start_date'    => $request->input('start_date'),
            'end_date'      => $request->input('end_date'),
            'show_archived' => $request->boolean('show_archived'),
        ];

        $statusMap = [
            'pending'  => 0,
            'approved' => 1,
            'rejected' => 2,
        ];

        if (!array_key_exists($filters['status'], $statusMap)) {
            $filters['status'] = 'all';
        }

        $baseQuery = MembershipPayment::with(['user.role', 'membership'])
            ->where('is_archive', $filters['show_archived'] ? 1 : 0);

        if ($filters['membership_id']) {
            $baseQuery->where('membership_id', $filters['membership_id']);
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
                    ->orWhereHas('membership', function ($membershipQuery) use ($like) {
                        $membershipQuery->where('name', 'like', $like);
                    })
                    ->orWhere('id', 'like', $like);
            });
        }

        if ($filters['start_date']) {
            $baseQuery->whereDate('created_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $baseQuery->whereDate('created_at', '<=', $filters['end_date']);
        }

        $statusTallies = [
            'all'      => (clone $baseQuery)->count(),
            'pending'  => (clone $baseQuery)->where('isapproved', $statusMap['pending'])->count(),
            'approved' => (clone $baseQuery)->where('isapproved', $statusMap['approved'])->count(),
            'rejected' => (clone $baseQuery)->where('isapproved', $statusMap['rejected'])->count(),
        ];

        $historyQuery = clone $baseQuery;
        if ($filters['status'] !== 'all') {
            $historyQuery->where('isapproved', $statusMap[$filters['status']]);
        }

        $queryParams = $request->query();

        $payments = (clone $historyQuery)
            ->orderByDesc('created_at')
            ->paginate(10)
            ->appends($queryParams);

        $stats = [
            'total'       => (clone $historyQuery)->count(),
            'members'     => (clone $historyQuery)->distinct('user_id')->count('user_id'),
            'memberships' => (clone $historyQuery)->distinct('membership_id')->count('membership_id'),
        ];

        $membershipOptions = Membership::orderBy('name')->get(['id', 'name', 'price', 'month']);
        $payrollRuns = PayrollRun::with('user')
            ->orderByDesc('processed_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.history.payments', [
            'payments'          => $payments,
            'membershipOptions' => $membershipOptions,
            'filters'           => $filters,
            'stats'             => $stats,
            'statusTallies'     => $statusTallies,
            'payrollRuns'       => $payrollRuns,
        ]);
    }
}
