<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MembershipPayment;
use App\Models\Membership;
use App\Models\User;
use App\Models\PayrollRun;
use App\Models\DeductionSetting;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'staff_id' => 'nullable|exists:users,id',
            'trainer_id' => 'nullable|exists:users,id',
            'membership_id' => 'nullable|exists:memberships,id',
            'member_id' => 'nullable|exists:users,id',
            'staff_sales_order' => 'nullable|in:most,least',
            'trainer_sales_order' => 'nullable|in:most,least',
        ]);

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $filters = [
            'staff_id' => $request->input('staff_id'),
            'trainer_id' => $request->input('trainer_id'),
            'membership_id' => $request->input('membership_id'),
            'member_id' => $request->input('member_id'),
            'staff_sales_order' => $request->input('staff_sales_order'),
            'trainer_sales_order' => $request->input('trainer_sales_order'),
        ];
        $hasMembershipFilter = !empty($filters['membership_id']);

        // Defaults: last 30 days
        $start = $startDate ? Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay() : Carbon::now()->subDays(29)->startOfDay();
        $end   = $endDate   ? Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay()   : Carbon::now()->endOfDay();

        // Base scope: approved, not archived, with membership relation
        $base = MembershipPayment::query()
            ->with([
                'membership:id,name,currency,price',
                'user:id,first_name,last_name,user_code,role_id',
            ])
            ->where('isapproved', 1)
            ->where('is_archive', 0)
            ->whereBetween('created_at', [$start, $end]);

        if ($filters['membership_id']) {
            $base->where('membership_id', $filters['membership_id']);
        }

        if ($filters['member_id']) {
            $base->where('user_id', $filters['member_id']);
        }

        $paymentRecords = (clone $base)->get();

        // Totals
        $totalSales = $paymentRecords->count();
        $totalRevenue = $paymentRecords->sum(function ($um) {
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
        $statusBase = MembershipPayment::where('is_archive', 0)->whereBetween('created_at', [$start, $end]);
        if ($filters['membership_id']) {
            $statusBase->where('membership_id', $filters['membership_id']);
        }
        if ($filters['member_id']) {
            $statusBase->where('user_id', $filters['member_id']);
        }

        $statusTallies = [
            'approved' => (clone $statusBase)->where('isapproved', 1)->count(),
            'pending'  => (clone $statusBase)->where('isapproved', 0)->count(),
            'rejected' => (clone $statusBase)->where('isapproved', 2)->count(),
        ];
        $statusTotal = array_sum($statusTallies);
        $conversionRates = [
            'approval' => $statusTotal > 0 ? round(($statusTallies['approved'] / $statusTotal) * 100, 1) : 0.0,
            'rejection' => $statusTotal > 0 ? round(($statusTallies['rejected'] / $statusTotal) * 100, 1) : 0.0,
        ];

        // Currency display (fallback)
        $currency = 'PHP';
        $any = (clone $base)->with('membership')->first();
        if ($any && optional($any->membership)->currency) {
            $currency = $any->membership->currency;
        }

        $roleFilters = [];
        if ($filters['staff_id']) {
            $roleFilters[] = 2;
        }
        if ($filters['trainer_id']) {
            $roleFilters[] = 5;
        }
        if (!$filters['staff_id'] && $filters['staff_sales_order']) {
            $roleFilters[] = 2;
        }
        if (!$filters['trainer_id'] && $filters['trainer_sales_order']) {
            $roleFilters[] = 5;
        }

        $payrollUserIds = collect([$filters['staff_id'], $filters['trainer_id']])
            ->filter()
            ->unique()
            ->values();

        $payrollBase = PayrollRun::query()
            ->with('user:id,role_id,first_name,last_name,email,user_code')
            ->whereBetween(DB::raw('COALESCE(processed_at, created_at)'), [$start, $end])
            ->when($payrollUserIds->isNotEmpty(), fn ($query) => $query->whereIn('user_id', $payrollUserIds))
            ->when(!empty($roleFilters), function ($query) use ($roleFilters) {
                $query->whereHas('user', function ($sub) use ($roleFilters) {
                    $sub->whereIn('role_id', array_unique($roleFilters));
                });
            });

        if ($filters['staff_sales_order']) {
            $payrollBase->orderBy('net_pay', $filters['staff_sales_order'] === 'least' ? 'asc' : 'desc');
        } elseif ($filters['trainer_sales_order']) {
            $payrollBase->orderBy('net_pay', $filters['trainer_sales_order'] === 'least' ? 'asc' : 'desc');
        }

        $payrollRuns = $payrollBase->get();
        $deductions = DeductionSetting::orderByDesc('id')->first();
        $appCutRate = (float) ($deductions->app_cut_rate ?? 0);
        $calculateAppCut = function ($run) use ($appCutRate) {
            $appCut = $run->deduction_app_cut ?? null;
            if (is_null($appCut) || (float) $appCut === 0.0) {
                $gross = (float) ($run->gross_pay ?? 0);
                return round($gross * ($appCutRate / 100), 2);
            }

            return (float) $appCut;
        };
        $payrollSummary = [
            'app_cut' => round($payrollRuns->sum(function ($run) use ($calculateAppCut) {
                return $calculateAppCut($run);
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
        $payrollRuns->each(function ($run) use (&$payrollDaily, $calculateAppCut) {
            $timestamp = $run->processed_at ?? $run->created_at;
            if (!$timestamp) {
                return;
            }

            $dayKey = Carbon::parse($timestamp)->toDateString();
            $roleId = optional($run->user)->role_id;
            $isTrainer = $roleId === 5;

            $appCut = $calculateAppCut($run);

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

        $payrollDetails = $payrollRuns->map(function ($run) use ($calculateAppCut) {
            $user = $run->user;
            $name = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'Unknown';
            $processedAt = $run->processed_at
                ? $run->processed_at->format('M d, Y g:i A')
                : ($run->created_at?->format('M d, Y g:i A') ?? '—');

            $appCut = $calculateAppCut($run);

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

        $membershipPlanRows = $paymentRecords
            ->groupBy('membership_id')
            ->map(function ($group) {
                $first = $group->first();
                $membership = $first ? $first->membership : null;
                $revenue = $group->sum(function ($payment) {
                    return (float) optional($payment->membership)->price ?: 0.0;
                });

                return [
                    'id' => $membership->id ?? null,
                    'name' => $membership->name ?? 'Unknown membership',
                    'price' => (float) optional($membership)->price ?: 0.0,
                    'sales' => $group->count(),
                    'revenue' => round($revenue, 2),
                ];
            })
            ->values()
            ->sortByDesc('sales')
            ->values();

        $memberSalesRows = $paymentRecords
            ->groupBy('user_id')
            ->map(function ($group) {
                $latestPayment = $group->sortByDesc('created_at')->first();
                $user = $latestPayment?->user;
                $name = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '—';
                $lastPaidAt = ($latestPayment && $latestPayment->created_at)
                    ? $latestPayment->created_at->format('M d, Y')
                    : '—';

                return [
                    'id' => $user->id ?? null,
                    'name' => $name !== '' ? $name : '—',
                    'user_code' => $user->user_code ?? '—',
                    'sales' => $group->count(),
                    'total' => round($group->sum(function ($payment) {
                        return (float) optional($payment->membership)->price ?: 0.0;
                    }), 2),
                    'last_membership' => optional($latestPayment?->membership)->name ?? '—',
                    'last_payment_at' => $lastPaidAt,
                ];
            })
            ->values()
            ->sortByDesc('sales')
            ->values();

        $payrollByUser = $payrollRuns
            ->groupBy('user_id')
            ->map(function ($runs) use ($calculateAppCut) {
                $firstRun = $runs->first();
                $user = $firstRun?->user;
                $name = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '—';
                $roleId = optional($user)->role_id;

                return [
                    'id' => $user->id ?? null,
                    'name' => $name !== '' ? $name : '—',
                    'user_code' => $user->user_code ?? '—',
                    'email' => $user->email ?? '—',
                    'role_id' => $roleId,
                    'role' => $roleId === 5 ? 'Trainer' : 'Staff',
                    'run_count' => $runs->count(),
                    'gross' => round($runs->sum(fn ($run) => (float) ($run->gross_pay ?? 0)), 2),
                    'net' => round($runs->sum(fn ($run) => (float) ($run->net_pay ?? 0)), 2),
                    'app_cut' => round($runs->sum(fn ($run) => $calculateAppCut($run)), 2),
                ];
            })
            ->values();

        $staffPayrollRows = $payrollByUser
            ->filter(fn ($row) => (int) ($row['role_id'] ?? 0) === 2)
            ->sortBy('name')
            ->values();

        $staffMembershipPayments = $staffPayrollRows
            ->mapWithKeys(function ($staffRow) use ($paymentRecords, $payrollRuns, $filters, $hasMembershipFilter) {
                $staffId = $staffRow['id'] ?? null;
                $staffCode = strtolower($staffRow['user_code'] ?? '');

                // Prefer processed (historical) membership payments saved with payroll runs
                $storedItems = collect();
                $runsForStaff = $payrollRuns->where('user_id', $staffId);
                foreach ($runsForStaff as $run) {
                    $stored = $run->processed_membership_payments_approved;
                    if (is_array($stored) && !empty($stored['items'] ?? [])) {
                        $storedItems = $storedItems->concat(collect($stored['items']));
                    }
                }

                // Ensure stored items carry membership_id when available so filters can apply
                $storedItems = $storedItems->map(function ($item) use ($paymentRecords) {
                    if (!isset($item['membership_id']) && isset($item['id'])) {
                        $match = $paymentRecords->firstWhere('id', $item['id']);
                        if ($match) {
                            $item['membership_id'] = $match->membership_id;
                        }
                    }
                    return $item;
                });

                $filteredStoredItems = $hasMembershipFilter
                    ? $storedItems->filter(function ($item) use ($filters) {
                        return isset($item['membership_id']) && (string) $item['membership_id'] === (string) $filters['membership_id'];
                    })->values()
                    : $storedItems->values();

                $fallbackItems = $paymentRecords
                    ->filter(function ($payment) use ($staffCode) {
                        $createdBy = strtolower(trim($payment->created_by ?? ''));
                        return $staffCode !== '' && $createdBy === $staffCode;
                    })
                    ->map(function ($payment) {
                        $member = $payment->user;
                        $membership = $payment->membership;
                        $currency = $membership->currency ?? 'PHP';
                        $price = (float) ($membership->price ?? 0);

                        return [
                            'id' => $payment->id,
                            'member_name' => trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: '—',
                            'member_code' => $member->user_code ?? '—',
                            'membership' => $membership->name ?? '—',
                            'membership_id' => $membership->id ?? null,
                            'currency' => $currency,
                            'price' => $price,
                            'created_at' => $payment->created_at ? $payment->created_at->format('M d, Y g:i A') : '—',
                            'expiration_at' => $payment->expiration_at ? Carbon::parse($payment->expiration_at)->format('M d, Y g:i A') : '—',
                        ];
                    })
                    ->values();

                $items = $hasMembershipFilter
                    ? ($filteredStoredItems->isNotEmpty() ? $filteredStoredItems : $fallbackItems)
                    : ($storedItems->isNotEmpty() ? $storedItems->values() : $fallbackItems);

                $total = $items->sum(fn ($item) => $item['price'] ?? 0);
                $firstItem = $items->first();
                $currency = is_array($firstItem) && array_key_exists('currency', $firstItem)
                    ? $firstItem['currency']
                    : 'PHP';

                return [
                    $staffId => [
                        'count' => $items->count(),
                        'total' => round($total, 2),
                        'currency' => $currency,
                        'items' => $items,
                    ],
                ];
            });

        $staffPayrollRows = $staffPayrollRows->map(function ($row) use ($staffMembershipPayments) {
            $row['membership_payments'] = $staffMembershipPayments->get($row['id'] ?? null, [
                'count' => 0,
                'total' => 0,
                'currency' => 'PHP',
                'items' => collect(),
            ]);
            return $row;
        });

        $trainerPayrollRows = $payrollByUser
            ->filter(fn ($row) => (int) ($row['role_id'] ?? 0) === 5)
            ->sortBy('name')
            ->values();

        $staffSalesOrderRows = $staffPayrollRows
            ->sortBy(
                function ($row) {
                    return (float) ($row['membership_payments']['total'] ?? 0);
                },
                SORT_REGULAR,
                ($filters['staff_sales_order'] ?? '') !== 'least'
            )
            ->values();

        $trainerSalesOrderRows = $trainerPayrollRows
            ->sortBy(
                function ($row) {
                    return (float) ($row['app_cut'] ?? 0);
                },
                SORT_REGULAR,
                ($filters['trainer_sales_order'] ?? '') !== 'least'
            )
            ->values();

        $perPage = max((int) $request->input('per_page', 10), 1);
        $paginateCollection = function ($collection, string $pageName) use ($perPage, $request) {
            $items = $collection instanceof \Illuminate\Support\Collection ? $collection : collect($collection);
            $page = LengthAwarePaginator::resolveCurrentPage($pageName);
            $currentPageItems = $items->slice(($page - 1) * $perPage, $perPage)->values();
            $paginator = new LengthAwarePaginator(
                $currentPageItems,
                $items->count(),
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'pageName' => $pageName,
                ]
            );

            return $paginator->appends($request->query());
        };

        $membershipPlanTable = $paginateCollection($membershipPlanRows, 'membership_page');
        $memberSalesTable = $paginateCollection($memberSalesRows, 'member_page');
        $staffPayrollTable = $paginateCollection($staffPayrollRows, 'staff_page');
        $trainerPayrollTable = $paginateCollection($trainerPayrollRows, 'trainer_page');
        $staffSalesOrderTable = $paginateCollection($staffSalesOrderRows, 'staff_order_page');
        $trainerSalesOrderTable = $paginateCollection($trainerSalesOrderRows, 'trainer_order_page');

        $formatUserOption = function (User $user) {
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $label = $name !== '' ? $name : 'Unnamed user';
            $code = $user->user_code ?? null;
            if ($code) {
                $label .= ' (' . $code . ')';
            }

            return [
                'id' => $user->id,
                'label' => $label,
            ];
        };

        $staffOptions = User::where('role_id', 2)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'user_code'])
            ->map($formatUserOption);

        $trainerOptions = User::where('role_id', 5)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'user_code'])
            ->map($formatUserOption);

        $memberOptions = User::where('role_id', 3)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'user_code'])
            ->map($formatUserOption);

        $membershipOptions = Membership::orderBy('name')
            ->get(['id', 'name', 'price'])
            ->map(function ($membership) {
                $label = $membership->name ?? 'Membership';
                if (!is_null($membership->price)) {
                    $label .= ' (' . number_format((float) $membership->price, 2) . ')';
                }
                return [
                    'id' => $membership->id,
                    'label' => $label,
                ];
            });

        $optionLookup = function ($options, $id) {
            if (!$id) {
                return null;
            }
            $collection = $options instanceof \Illuminate\Support\Collection ? $options : collect($options);
            $match = $collection->firstWhere('id', (int) $id);
            return is_array($match) ? ($match['label'] ?? null) : ($match->label ?? null);
        };

        $filterLabels = [
            'staff' => $optionLookup($staffOptions, $filters['staff_id']),
            'trainer' => $optionLookup($trainerOptions, $filters['trainer_id']),
            'member' => $optionLookup($memberOptions, $filters['member_id']),
            'membership' => $optionLookup($membershipOptions, $filters['membership_id']),
        ];

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
            'conversionRates' => $conversionRates,
            'membershipPlanTable' => $membershipPlanTable,
            'memberSalesTable' => $memberSalesTable,
            'staffPayrollTable' => $staffPayrollTable,
            'trainerPayrollTable' => $trainerPayrollTable,
            'staffSalesOrderTable' => $staffSalesOrderTable,
            'trainerSalesOrderTable' => $trainerSalesOrderTable,
            'membershipPlanAll' => $membershipPlanRows,
            'memberSalesAll' => $memberSalesRows,
            'staffPayrollAll' => $staffPayrollRows,
            'trainerPayrollAll' => $trainerPayrollRows,
            'staffSalesOrderAll' => $staffSalesOrderRows,
            'trainerSalesOrderAll' => $trainerSalesOrderRows,
            'filters' => $filters,
            'filterLabels' => $filterLabels,
            'staffOptions' => $staffOptions,
            'trainerOptions' => $trainerOptions,
            'memberOptions' => $memberOptions,
            'membershipOptions' => $membershipOptions,
        ]);
    }

    public function report(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'search' => 'nullable|string|max:255',
            'focus' => 'nullable|in:member,trainer,staff,membership,date',
            'order' => 'nullable|in:most,least',
            'plan_tier' => 'nullable|in:premium,half',
            'membership_id' => 'nullable|exists:memberships,id',
            'date_preset' => 'nullable|in:today,yesterday,last_7,last_30,this_week,last_week,this_month,last_month,this_quarter,last_quarter,this_year,last_year,all_time,custom',
        ]);

        $startInput = $request->input('start_date');
        $endInput = $request->input('end_date');
        $startTimeInput = $request->input('start_time');
        $endTimeInput = $request->input('end_time');
        [$start, $end, $datePreset] = $this->resolveDateRange(
            $request->input('date_preset'),
            $startInput,
            $endInput,
            $startTimeInput,
            $endTimeInput
        );

        $search = trim((string) $request->input('search', ''));
        $focus = $request->input('focus', 'member');
        $focusSupportsOrder = in_array($focus, ['member', 'trainer', 'staff'], true);
        $orderInput = $request->input('order', 'most');
        $order = $focusSupportsOrder && $orderInput === 'least' ? 'least' : ($focusSupportsOrder ? 'most' : null);
        $planTier = $request->input('plan_tier');
        $membershipId = $request->input('membership_id');

        $paymentsQuery = MembershipPayment::query()
            ->with([
                'membership:id,name,currency,price',
                'user:id,first_name,last_name,user_code,role_id',
            ])
            ->where('isapproved', 1)
            ->where('is_archive', 0)
            ->whereBetween('created_at', [$start, $end]);

        if ($membershipId) {
            $paymentsQuery->where('membership_id', $membershipId);
        }

        if ($search) {
            $paymentsQuery->where(function ($query) use ($search) {
                $query
                    ->whereHas('user', function ($sub) use ($search) {
                        $sub->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                            ->orWhere('user_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('membership', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($planTier) {
            $paymentsQuery->whereHas('membership', function ($sub) use ($planTier) {
                $keyword = $planTier === 'half' ? 'half' : 'premium';
                $sub->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($keyword) . '%']);
            });
        }

        $payments = $paymentsQuery->get();
        $currency = 'PHP';
        $firstPayment = $payments->first();
        if ($firstPayment && optional($firstPayment->membership)->currency) {
            $currency = $firstPayment->membership->currency;
        }

        $membershipRevenue = $payments->sum(function ($payment) {
            return (float) optional($payment->membership)->price ?: 0.0;
        });
        $membershipCount = $payments->count();

        $payrollBase = PayrollRun::query()
            ->with('user:id,role_id,first_name,last_name,user_code')
            ->whereBetween(DB::raw('COALESCE(processed_at, created_at)'), [$start, $end]);

        if ($search) {
            $payrollBase->whereHas('user', function ($query) use ($search) {
                $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('user_code', 'like', "%{$search}%");
            });
        }

        $classCommission = (clone $payrollBase)
            ->whereHas('user', fn ($query) => $query->where('role_id', 5))
            ->sum('net_pay');
        $classCommission = round((float) $classCommission, 2);

        $totalSales = round($membershipRevenue + $classCommission, 2);

        $focusRows = $this->buildSalesReportRows($focus, $order ?? 'most', $payments, $payrollBase);
        $perPage = 10;
        if (!($focusRows instanceof LengthAwarePaginator)) {
            $items = $focusRows instanceof \Illuminate\Support\Collection ? $focusRows : collect($focusRows ?? []);
            $page = LengthAwarePaginator::resolveCurrentPage('page');
            $paginator = new LengthAwarePaginator(
                $items->slice(($page - 1) * $perPage, $perPage)->values(),
                $items->count(),
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'pageName' => 'page',
                ]
            );
            $focusRows = $paginator->appends($request->query());
        }

        $rangeLabel = $start->format('Y') === $end->format('Y')
            ? $start->format('Y')
            : $start->format('M d, Y') . ' → ' . $end->format('M d, Y');

        $membershipOptions = Membership::orderBy('name')->get(['id', 'name']);
        $membershipLabel = optional($membershipOptions->firstWhere('id', (int) $membershipId))->name ?? 'All Memberships';

        $datePresetLabels = [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7' => 'Last 7 Days',
            'last_30' => 'Last 30 Days',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'last_quarter' => 'Last Quarter',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
            'all_time' => 'All Time',
            'custom' => 'Custom Date Range',
        ];
        $datePresetLabel = $datePresetLabels[$datePreset] ?? 'Custom Date Range';

        return view('admin.sales.report', [
            'rangeLabel' => $rangeLabel,
            'rangeYear' => $start->format('Y'),
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'startTime' => $start->format('H:i'),
            'endTime' => $end->format('H:i'),
            'searchTerm' => $search,
            'focus' => $focus,
            'order' => $order,
            'planTier' => $planTier,
            'currency' => $currency,
            'summary' => [
                'membership_revenue' => round($membershipRevenue, 2),
                'membership_count' => $membershipCount,
                'class_commission' => $classCommission,
                'total_sales' => $totalSales,
            ],
            'focusRows' => $focusRows,
            'membershipOptions' => $membershipOptions,
            'selectedMembershipId' => $membershipId,
            'selectedMembershipLabel' => $membershipLabel,
            'datePreset' => $datePreset,
            'datePresetLabel' => $datePresetLabel,
            'datePresetLabels' => $datePresetLabels,
        ]);
    }

    public function reports(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'date_preset' => 'nullable|in:today,yesterday,last_7,last_30,this_week,last_week,this_month,last_month,this_quarter,last_quarter,this_year,last_year,all_time,custom',
        ]);

        $startInput = $request->input('start_date');
        $endInput   = $request->input('end_date');
        $presetInput = $request->input('date_preset');
        $preset = $presetInput ?: (($startInput || $endInput) ? 'custom' : 'last_30');

        [$start, $end, $datePreset] = $this->resolveDateRange($preset, $startInput, $endInput);
        $startValue = $start->toDateString();
        $endValue = $end->toDateString();

        $paymentsQuery = MembershipPayment::query()
            ->with([
                'membership:id,name,price,currency',
                'user:id,first_name,last_name,user_code',
            ])
            ->where('isapproved', 1)
            ->where('is_archive', 0)
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at');

        $paymentsPage = $paymentsQuery->paginate(10)->withQueryString();

        $allPayments = (clone $paymentsQuery)->get();
        $currency = optional($allPayments->first()?->membership)->currency ?: 'PHP';
        $membershipRevenue = $allPayments->sum(function ($payment) {
            return (float) optional($payment->membership)->price ?: 0.0;
        });

        $payrollRuns = PayrollRun::query()
            ->with('user:id,first_name,last_name,user_code,role_id')
            ->whereBetween(DB::raw('COALESCE(processed_at, created_at)'), [$start, $end])
            ->get();

        $cost = $payrollRuns->sum(function ($run) {
            return (float) ($run->net_pay ?? 0);
        });

        $deductions = DeductionSetting::orderByDesc('id')->first();
        $appCutRate = (float) ($deductions->app_cut_rate ?? 0);
        $calculateAppCut = function ($run) use ($appCutRate) {
            $stored = $run->deduction_app_cut ?? null;
            if (!is_null($stored)) {
                return (float) $stored;
            }

            $gross = (float) ($run->gross_pay ?? 0);
            return round($gross * ($appCutRate / 100), 2);
        };
        $appCutRevenue = $payrollRuns->sum(fn ($run) => $calculateAppCut($run));

        $totalRevenue = round($membershipRevenue + $appCutRevenue, 2);
        $profit = round($totalRevenue - $cost, 2);

        $mapPayment = function ($payment) use ($currency) {
            $member = $payment->user;
            $membership = $payment->membership;

            return [
                'id' => $payment->id,
                'member' => $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : '—',
                'user_code' => $member->user_code ?? '—',
                'membership' => $membership->name ?? '—',
                'amount' => number_format((float) ($membership->price ?? 0), 2),
                'currency' => $membership->currency ?? $currency,
                'created_at' => optional($payment->created_at)->format('M d, Y g:i A') ?? '—',
            ];
        };

        $mappedPage = $paymentsPage->getCollection()->map($mapPayment);
        $membershipPayments = new LengthAwarePaginator(
            $mappedPage,
            $paymentsPage->total(),
            $paymentsPage->perPage(),
            $paymentsPage->currentPage(),
            [
                'path' => $paymentsPage->path(),
                'pageName' => $paymentsPage->getPageName(),
            ]
        );
        $membershipPayments->appends($request->query());

        $printAllPayments = $allPayments->map($mapPayment)->values();

        $summary = [
            'total_revenue' => $totalRevenue,
            'membership_revenue' => round($membershipRevenue, 2),
            'app_cut_revenue' => round($appCutRevenue, 2),
            'cost' => round($cost, 2),
            'profit' => $profit,
            'currency' => $currency,
            'period_label' => $start->format('M d, Y') . ' → ' . $end->format('M d, Y'),
        ];

        // Build grouped tables for quick switching in the reports view
        $tableScope = $request->input('table_scope', 'payments');
        $paymentRecords = $allPayments;

        $membershipPlanRows = $paymentRecords
            ->groupBy('membership_id')
            ->map(function ($group) {
                $first = $group->first();
                $membership = $first ? $first->membership : null;
                $revenue = $group->sum(function ($payment) {
                    return (float) optional($payment->membership)->price ?: 0.0;
                });

                return [
                    'id' => $membership->id ?? null,
                    'name' => $membership->name ?? 'Unknown membership',
                    'price' => (float) optional($membership)->price ?: 0.0,
                    'sales' => $group->count(),
                    'revenue' => round($revenue, 2),
                ];
            })
            ->values()
            ->sortByDesc('sales')
            ->values();

        $memberSalesRows = $paymentRecords
            ->groupBy('user_id')
            ->map(function ($group) {
                $latestPayment = $group->sortByDesc('created_at')->first();
                $user = $latestPayment?->user;
                $name = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '—';
                $lastPaidAt = ($latestPayment && $latestPayment->created_at)
                    ? $latestPayment->created_at->format('M d, Y')
                    : '—';

                return [
                    'id' => $user->id ?? null,
                    'name' => $name !== '' ? $name : '—',
                    'user_code' => $user->user_code ?? '—',
                    'sales' => $group->count(),
                    'total' => round($group->sum(function ($payment) {
                        return (float) optional($payment->membership)->price ?: 0.0;
                    }), 2),
                    'last_membership' => optional($latestPayment?->membership)->name ?? '—',
                    'last_payment_at' => $lastPaidAt,
                ];
            })
            ->values()
            ->sortByDesc('sales')
            ->values();

        $payrollByUser = $payrollRuns
            ->groupBy('user_id')
            ->map(function ($runs) use ($calculateAppCut) {
                $firstRun = $runs->first();
                $user = $firstRun?->user;
                $name = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '—';
                $roleId = optional($user)->role_id;

                return [
                    'id' => $user->id ?? null,
                    'name' => $name !== '' ? $name : '—',
                    'user_code' => $user->user_code ?? '—',
                    'email' => $user->email ?? '—',
                    'role_id' => $roleId,
                    'role' => $roleId === 5 ? 'Trainer' : 'Staff',
                    'run_count' => $runs->count(),
                    'gross' => round($runs->sum(fn ($run) => (float) ($run->gross_pay ?? 0)), 2),
                    'net' => round($runs->sum(fn ($run) => (float) ($run->net_pay ?? 0)), 2),
                    'app_cut' => round($runs->sum(fn ($run) => $calculateAppCut($run)), 2),
                ];
            })
            ->values();

        $staffPayrollRows = $payrollByUser
            ->filter(fn ($row) => (int) ($row['role_id'] ?? 0) === 2)
            ->sortBy('name')
            ->values();

        $staffMembershipPayments = $staffPayrollRows
            ->mapWithKeys(function ($staffRow) use ($paymentRecords) {
                $staffId = $staffRow['id'] ?? null;
                $staffCode = strtolower(trim($staffRow['user_code'] ?? ''));

                $items = $paymentRecords
                    ->filter(function ($payment) use ($staffCode) {
                        $createdBy = strtolower(trim($payment->created_by ?? ''));
                        return $staffCode !== '' && $createdBy === $staffCode;
                    })
                    ->map(function ($payment) {
                        $member = $payment->user;
                        $membership = $payment->membership;
                        $currency = $membership->currency ?? 'PHP';
                        $price = (float) ($membership->price ?? 0);

                        return [
                            'id' => $payment->id,
                            'member_name' => trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: '—',
                            'member_code' => $member->user_code ?? '—',
                            'membership' => $membership->name ?? '—',
                            'currency' => $currency,
                            'price' => $price,
                            'created_at' => $payment->created_at ? $payment->created_at->format('M d, Y g:i A') : '—',
                            'expiration_at' => $payment->expiration_at ? Carbon::parse($payment->expiration_at)->format('M d, Y g:i A') : '—',
                            'membership_id' => $membership->id ?? null,
                        ];
                    })
                    ->values();

                $total = $items->sum(fn ($item) => $item['price'] ?? 0);
                $firstItem = $items->first();
                $currency = is_array($firstItem) && array_key_exists('currency', $firstItem)
                    ? $firstItem['currency']
                    : 'PHP';

                return [
                    $staffId => [
                        'count' => $items->count(),
                        'total' => round($total, 2),
                        'currency' => $currency,
                        'items' => $items,
                    ],
                ];
            });

        $staffPayrollRows = $staffPayrollRows->map(function ($row) use ($staffMembershipPayments, $currency) {
            $row['membership_payments'] = $staffMembershipPayments->get($row['id'] ?? null, [
                'count' => 0,
                'total' => 0,
                'currency' => $currency,
                'items' => collect(),
            ]);
            return $row;
        });

        $trainerPayrollRows = $payrollByUser
            ->filter(fn ($row) => (int) ($row['role_id'] ?? 0) === 5)
            ->sortBy('name')
            ->values();

        $staffSalesOrderRows = $staffPayrollRows
            ->sortBy(
                fn ($row) => (float) ($row['membership_payments']['total'] ?? 0),
                SORT_REGULAR,
                true
            )
            ->values();

        $trainerSalesOrderRows = $trainerPayrollRows
            ->sortBy(
                fn ($row) => (float) ($row['app_cut'] ?? 0),
                SORT_REGULAR,
                true
            )
            ->values();

        $perPage = max((int) $request->input('per_page', 10), 1);
        $paginateCollection = function ($collection, string $pageName) use ($perPage, $request) {
            $items = $collection instanceof \Illuminate\Support\Collection ? $collection : collect($collection);
            $page = LengthAwarePaginator::resolveCurrentPage($pageName);
            $currentPageItems = $items->slice(($page - 1) * $perPage, $perPage)->values();
            $paginator = new LengthAwarePaginator(
                $currentPageItems,
                $items->count(),
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'pageName' => $pageName,
                ]
            );

            return $paginator->appends($request->query());
        };

        $membershipPlanTable = $paginateCollection($membershipPlanRows, 'membership_page');
        $memberSalesTable = $paginateCollection($memberSalesRows, 'member_page');
        $staffSalesOrderTable = $paginateCollection($staffSalesOrderRows, 'staff_order_page');
        $trainerSalesOrderTable = $paginateCollection($trainerSalesOrderRows, 'trainer_order_page');

        return view('admin.sales.reports', [
            'summary' => $summary,
            'membershipPayments' => $membershipPayments,
            'membershipPaymentsAll' => $printAllPayments,
            'datePreset' => $datePreset,
            'startDate' => $startValue,
            'endDate' => $endValue,
            'tableScope' => $tableScope,
            'membershipPlanTable' => $membershipPlanTable,
            'memberSalesTable' => $memberSalesTable,
            'staffSalesOrderTable' => $staffSalesOrderTable,
            'trainerSalesOrderTable' => $trainerSalesOrderTable,
        ]);
    }

    protected function buildSalesReportRows(string $focus, string $order, $payments, $payrollBase)
    {
        $orderDirection = $order === 'least' ? 'asc' : 'desc';
        $rows = collect();

        if ($focus === 'trainer' || $focus === 'staff') {
            $roleId = $focus === 'trainer' ? 5 : 2;
            $runs = (clone $payrollBase)
                ->whereHas('user', fn ($query) => $query->where('role_id', $roleId))
                ->get();

            $rows = $runs->groupBy('user_id')->map(function ($group) use ($focus) {
                $latest = $group->sortByDesc(function ($run) {
                    return $run->processed_at ?? $run->created_at;
                })->first();

                $user = $latest?->user;
                $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                $label = $name ?: 'Unknown ' . ($focus === 'trainer' ? 'trainer' : 'staff');
                $code = $user->user_code ?? null;
                $lastDate = $latest?->processed_at ?? $latest?->created_at;

                return [
                    'label' => $code ? "{$label} ({$code})" : $label,
                    'type' => ucfirst($focus),
                    'sales' => $group->count(),
                    'revenue' => round($group->sum(fn ($run) => (float) ($run->net_pay ?? 0)), 2),
                    'last_sale' => $lastDate ? Carbon::parse($lastDate)->format('M d, Y') : '—',
                ];
            });
        } elseif ($focus === 'membership') {
            $rows = $payments->groupBy('membership_id')->map(function ($group) {
                $membership = $group->first()?->membership;
                $label = $membership?->name ?: 'Unassigned membership';

                return [
                    'label' => $label,
                    'type' => 'Membership',
                    'sales' => $group->count(),
                    'revenue' => round($group->sum(function ($payment) {
                        return (float) optional($payment->membership)->price ?: 0.0;
                    }), 2),
                    'last_sale' => optional($group->sortByDesc('created_at')->first()?->created_at)->format('M d, Y') ?: '—',
                ];
            });
        } elseif ($focus === 'date') {
            $rows = $payments->groupBy(function ($payment) {
                return Carbon::parse($payment->created_at)->format('Y-m');
            })->map(function ($group, $monthKey) {
                $label = Carbon::createFromFormat('Y-m', $monthKey)->format('M Y');

                return [
                    'label' => $label,
                    'type' => 'Month',
                    'sales' => $group->count(),
                    'revenue' => round($group->sum(function ($payment) {
                        return (float) optional($payment->membership)->price ?: 0.0;
                    }), 2),
                    'last_sale' => optional($group->sortByDesc('created_at')->first()?->created_at)->format('M d, Y') ?: '—',
                ];
            });
        } else {
            $rows = $payments->groupBy('user_id')->map(function ($group) {
                $user = $group->first()?->user;
                $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                $label = $name ?: 'Member';
                $code = $user->user_code ?? null;

                return [
                    'label' => $code ? "{$label} ({$code})" : $label,
                    'type' => 'Member',
                    'sales' => $group->count(),
                    'revenue' => round($group->sum(function ($payment) {
                        return (float) optional($payment->membership)->price ?: 0.0;
                    }), 2),
                    'last_sale' => optional($group->sortByDesc('created_at')->first()?->created_at)->format('M d, Y') ?: '—',
                ];
            });
        }

        return $orderDirection === 'asc'
            ? $rows->sortBy('revenue')->values()
            : $rows->sortByDesc('revenue')->values();
    }

    protected function resolveDateRange(?string $preset, ?string $startInput, ?string $endInput, ?string $startTimeInput = null, ?string $endTimeInput = null): array
    {
        $today = Carbon::now();
        $preset = $preset ?: 'this_year';
        $start = $today->copy()->startOfYear();
        $end = $today->copy()->endOfDay();
        $startTime = $startTimeInput ?: '00:00';
        $endTime = $endTimeInput ?: '23:59';

        switch ($preset) {
            case 'today':
                $start = $today->copy()->startOfDay();
                $end = $today->copy()->endOfDay();
                break;
            case 'yesterday':
                $start = $today->copy()->subDay()->startOfDay();
                $end = $today->copy()->subDay()->endOfDay();
                break;
            case 'last_7':
                $start = $today->copy()->subDays(6)->startOfDay();
                $end = $today->copy()->endOfDay();
                break;
            case 'last_30':
                $start = $today->copy()->subDays(29)->startOfDay();
                $end = $today->copy()->endOfDay();
                break;
            case 'this_week':
                $start = $today->copy()->startOfWeek();
                $end = $today->copy()->endOfWeek();
                break;
            case 'last_week':
                $start = $today->copy()->subWeek()->startOfWeek();
                $end = $today->copy()->subWeek()->endOfWeek();
                break;
            case 'this_month':
                $start = $today->copy()->startOfMonth();
                $end = $today->copy()->endOfMonth();
                break;
            case 'last_month':
                $lastMonth = $today->copy()->subMonth();
                $start = $lastMonth->copy()->startOfMonth();
                $end = $lastMonth->copy()->endOfMonth();
                break;
            case 'this_quarter':
                $start = $today->copy()->firstOfQuarter()->startOfDay();
                $end = $today->copy()->lastOfQuarter()->endOfDay();
                break;
            case 'last_quarter':
                $lastQuarter = $today->copy()->subQuarter();
                $start = $lastQuarter->copy()->firstOfQuarter()->startOfDay();
                $end = $lastQuarter->copy()->lastOfQuarter()->endOfDay();
                break;
            case 'this_year':
                $start = $today->copy()->startOfYear();
                $end = $today->copy()->endOfDay();
                break;
            case 'last_year':
                $lastYear = $today->copy()->subYear();
                $start = $lastYear->copy()->startOfYear();
                $end = $lastYear->copy()->endOfYear();
                break;
            case 'all_time':
                $start = Carbon::create(2000, 1, 1, 0, 0, 0);
                $end = $today->copy()->endOfDay();
                break;
            case 'custom':
            default:
                $preset = 'custom';
                $start = $startInput
                    ? Carbon::createFromFormat('Y-m-d H:i', "{$startInput} {$startTime}")
                    : $today->copy()->startOfYear();
                $end = $endInput
                    ? Carbon::createFromFormat('Y-m-d H:i', "{$endInput} {$endTime}")->endOfMinute()
                    : $today->copy()->endOfDay();
                break;
        }

        if ($end->lt($start)) {
            $end = $start->copy()->endOfDay();
        }

        return [$start, $end, $preset];
    }
}
