<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MembershipPayment;
use App\Models\Membership;
use App\Models\PayrollRun;
use App\Models\DeductionSetting;
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
        $base = MembershipPayment::query()
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
                DB::raw("SUM(COALESCE((SELECT price FROM memberships WHERE memberships.id = membership_payments.membership_id), 0)) as revenue")
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
                DB::raw("SUM(COALESCE((SELECT price FROM memberships WHERE memberships.id = membership_payments.membership_id), 0)) as revenue")
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
            'approved' => MembershipPayment::where('is_archive', 0)->where('isapproved', 1)->whereBetween('created_at', [$start, $end])->count(),
            'pending'  => MembershipPayment::where('is_archive', 0)->where('isapproved', 0)->whereBetween('created_at', [$start, $end])->count(),
            'rejected' => MembershipPayment::where('is_archive', 0)->where('isapproved', 2)->whereBetween('created_at', [$start, $end])->count(),
        ];

        // Currency display (fallback)
        $currency = 'PHP';
        $any = (clone $base)->with('membership')->first();
        if ($any && optional($any->membership)->currency) {
            $currency = $any->membership->currency;
        }

        $payrollBase = PayrollRun::query()
            ->with('user:id,role_id,first_name,last_name,email,user_code')
            ->whereBetween(DB::raw('COALESCE(processed_at, created_at)'), [$start, $end]);

        $payrollRuns = $payrollBase->get();
        $deductions = DeductionSetting::orderByDesc('id')->first();
        $appCutRate = (float) ($deductions->app_cut_rate ?? 0);
        $payrollSummary = [
            'app_cut' => round($payrollRuns->sum(function ($run) use ($appCutRate) {
                $stored = $run->deduction_app_cut ?? null;
                if (!is_null($stored) && (float) $stored !== 0.0) {
                    return (float) $stored;
                }

                $gross = (float) ($run->gross_pay ?? 0);
                return round($gross * ($appCutRate / 100), 2);
            }), 2),
            'trainer_net' => round($payrollRuns->filter(fn ($run) => optional($run->user)->role_id === 5)->sum(fn ($run) => (float) ($run->net_pay ?? 0)), 2),
            'staff_net' => round($payrollRuns->filter(fn ($run) => optional($run->user)->role_id === 2)->sum(fn ($run) => (float) ($run->net_pay ?? 0)), 2),
            'gross' => round($payrollRuns->sum(fn ($run) => (float) ($run->gross_pay ?? 0)), 2),
            'net' => round($payrollRuns->sum(fn ($run) => (float) ($run->net_pay ?? 0)), 2),
            'run_count' => $payrollRuns->count(),
            'period_label' => $start->format('M d, Y') . ' → ' . $end->format('M d, Y'),
        ];

        $payrollDetails = $payrollRuns->map(function ($run) use ($appCutRate) {
            $user = $run->user;
            $name = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'Unknown';
            $processedAt = $run->processed_at
                ? $run->processed_at->format('M d, Y g:i A')
                : ($run->created_at?->format('M d, Y g:i A') ?? '—');

            $appCut = $run->deduction_app_cut ?? null;
            if (is_null($appCut) || (float) $appCut === 0.0) {
                $gross = (float) ($run->gross_pay ?? 0);
                $appCut = round($gross * ($appCutRate / 100), 2);
            }

            return [
                'id' => $run->id,
                'name' => $name !== '' ? $name : '—',
                'email' => $user->email ?? '—',
                'user_code' => $user->user_code ?? '—',
                'role' => optional($user)->role_id === 5 ? 'Trainer' : 'Staff',
                'period' => $run->period_month ?? '—',
                'processed_at' => $processedAt,
                'net' => number_format((float) ($run->net_pay ?? 0), 2),
                'app_cut' => number_format((float) $appCut, 2),
            ];
        })->values();

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
            'payrollSummary' => $payrollSummary,
            'payrollDetails' => $payrollDetails,
        ]);
    }
}
