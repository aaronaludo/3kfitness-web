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

        // Finished payrolls over time (net pay + app cut)
        $payrollDaily = [];
        $payrollRuns->each(function ($run) use (&$payrollDaily, $appCutRate) {
            $timestamp = $run->processed_at ?? $run->created_at;
            if (!$timestamp) {
                return;
            }

            $dayKey = Carbon::parse($timestamp)->toDateString();
            $roleId = optional($run->user)->role_id;
            $isTrainer = $roleId === 5;

            $appCut = $run->deduction_app_cut ?? null;
            if (is_null($appCut) || (float) $appCut === 0.0) {
                $gross = (float) ($run->gross_pay ?? 0);
                $appCut = round($gross * ($appCutRate / 100), 2);
            }

            $bucket = $payrollDaily[$dayKey] ?? ['staff_net' => 0, 'trainer_net' => 0, 'app_cut' => 0];
            $bucket['app_cut'] += (float) $appCut;
            if ($isTrainer) {
                $bucket['trainer_net'] += (float) ($run->net_pay ?? 0);
            } else {
                $bucket['staff_net'] += (float) ($run->net_pay ?? 0);
            }
            $payrollDaily[$dayKey] = $bucket;
        });

        $payrollLabels = [];
        $payrollStaffSeries = [];
        $payrollTrainerSeries = [];
        $payrollAppCutSeries = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $dayKey = $cursor->toDateString();
            $payrollLabels[] = $cursor->format('M d');
            $bucket = $payrollDaily[$dayKey] ?? ['staff_net' => 0, 'trainer_net' => 0, 'app_cut' => 0];
            $payrollStaffSeries[] = round($bucket['staff_net'], 2);
            $payrollTrainerSeries[] = round($bucket['trainer_net'], 2);
            $payrollAppCutSeries[] = round($bucket['app_cut'], 2);
            $cursor->addDay();
        }

        // Revenue/Cost/Profit per day (membership revenue + app cut vs payroll cost)
        $financeLabels = [];
        $financeRevenueSeries = [];
        $financeCostSeries = [];
        $financeProfitSeries = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $dayKey = $cursor->toDateString();
            $financeLabels[] = $cursor->format('M d');
            $membershipRevenue = (float) optional($dailyRows->get($dayKey))->revenue ?: 0.0;
            $payrollBucket = $payrollDaily[$dayKey] ?? ['staff_net' => 0, 'trainer_net' => 0, 'app_cut' => 0];
            $dailyRevenue = round($membershipRevenue + (float) ($payrollBucket['app_cut'] ?? 0), 2);
            $dailyCost = round(
                (float) ($payrollBucket['staff_net'] ?? 0) + (float) ($payrollBucket['trainer_net'] ?? 0),
                2
            );
            $dailyProfit = round($dailyRevenue - $dailyCost, 2);
            $financeRevenueSeries[] = $dailyRevenue;
            $financeCostSeries[] = $dailyCost;
            $financeProfitSeries[] = $dailyProfit;
            $cursor->addDay();
        }

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

        // Pie chart: finished payroll breakdown
        $pieLabels = ['Staff payroll (net)', 'Trainer payroll (net)', '3kfitness app cut'];
        $pieValues = [
            (float) ($payrollSummary['staff_net'] ?? 0),
            (float) ($payrollSummary['trainer_net'] ?? 0),
            (float) ($payrollSummary['app_cut'] ?? 0),
        ];

        // Finance overview: revenue = membership revenue + app cut; cost = staff + trainer net; profit = revenue - cost
        $revenueTotal = round((float) $totalRevenue + (float) ($payrollSummary['app_cut'] ?? 0), 2);
        $costTotal = round(
            (float) ($payrollSummary['staff_net'] ?? 0) + (float) ($payrollSummary['trainer_net'] ?? 0),
            2
        );
        $profitTotal = round($revenueTotal - $costTotal, 2);

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
            'payrollLabels' => $payrollLabels,
            'payrollStaffSeries' => $payrollStaffSeries,
            'payrollTrainerSeries' => $payrollTrainerSeries,
            'payrollAppCutSeries' => $payrollAppCutSeries,
            'revenueTotal' => $revenueTotal,
            'costTotal' => $costTotal,
            'profitTotal' => $profitTotal,
            'financeLabels' => $financeLabels,
            'financeRevenueSeries' => $financeRevenueSeries,
            'financeCostSeries' => $financeCostSeries,
            'financeProfitSeries' => $financeProfitSeries,
        ]);
    }
}
