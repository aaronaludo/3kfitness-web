<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserMembership;
use App\Models\Membership;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        // Defaults: last 30 days
        $start = $startDate ? Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay() : Carbon::now()->subDays(29)->startOfDay();
        $end   = $endDate   ? Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay()   : Carbon::now()->endOfDay();

        // Base scope: approved, not archived, with membership relation
        $base = UserMembership::query()
            ->with(['membership:id,name,currency,price'])
            ->where('isapproved', 1)
            ->where('is_archive', 0)
            ->whereBetween('created_at', [$start, $end]);

        // Totals
        $totalSales = (clone $base)->count();
        $totalRevenue = (clone $base)
            ->get()
            ->sum(function ($um) {
                return (float) optional($um->membership)->price ?: 0.0;
            });

        // Daily revenue series
        $dailyRows = (clone $base)
            ->select([
                DB::raw("DATE(created_at) as day"),
                DB::raw("SUM(COALESCE((SELECT price FROM memberships WHERE memberships.id = user_memberships.membership_id), 0)) as revenue")
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('day');

        $labels = [];
        $series = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $dayKey = $cursor->toDateString();
            $labels[] = $cursor->format('M d');
            $series[] = (float) optional($dailyRows->get($dayKey))->revenue ?: 0.0;
            $cursor->addDay();
        }

        // Revenue by membership (for pie)
        $byMembership = (clone $base)
            ->select([
                'membership_id',
                DB::raw("SUM(COALESCE((SELECT price FROM memberships WHERE memberships.id = user_memberships.membership_id), 0)) as revenue")
            ])
            ->groupBy('membership_id')
            ->get();

        $membershipIds = $byMembership->pluck('membership_id')->filter()->values();
        $membershipNames = Membership::whereIn('id', $membershipIds)->pluck('name', 'id');

        $pieLabels = [];
        $pieValues = [];
        foreach ($byMembership as $row) {
            $name = $row->membership_id ? ($membershipNames[$row->membership_id] ?? ('#' . $row->membership_id)) : 'No Plan';
            $pieLabels[] = $name;
            $pieValues[] = (float) $row->revenue;
        }

        // Status tallies for the period (for quick insights)
        $statusTallies = [
            'approved' => UserMembership::where('is_archive', 0)->where('isapproved', 1)->whereBetween('created_at', [$start, $end])->count(),
            'pending'  => UserMembership::where('is_archive', 0)->where('isapproved', 0)->whereBetween('created_at', [$start, $end])->count(),
            'rejected' => UserMembership::where('is_archive', 0)->where('isapproved', 2)->whereBetween('created_at', [$start, $end])->count(),
        ];

        // Currency display (fallback)
        $currency = 'PHP';
        $any = (clone $base)->with('membership')->first();
        if ($any && optional($any->membership)->currency) {
            $currency = $any->membership->currency;
        }

        return view('admin.sales.index', [
            'start' => $start,
            'end' => $end,
            'currency' => $currency,
            'totalSales' => $totalSales,
            'totalRevenue' => $totalRevenue,
            'labels' => $labels,
            'series' => $series,
            'pieLabels' => $pieLabels,
            'pieValues' => $pieValues,
            'statusTallies' => $statusTallies,
        ]);
    }
}


