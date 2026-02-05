@extends('layouts.admin')
@section('title', 'Process Payroll')

@section('content')
    @php
        $deductionSettings = $deductionSettings ?? [
            'sss_rate' => 4.5,
            'philhealth_rate' => 2.5,
            'pagibig_rate' => 2,
            'pagibig_cap' => 5000,
            'app_cut_rate' => 0,
            'processing_days' => [],
            'processing_day_ranges' => [],
        ];
        $summaryTotalsSource = $summariesAll ?? ($summaries ?? collect());
        $summaryTotalsCollection = $summaryTotalsSource instanceof \Illuminate\Pagination\AbstractPaginator
            ? collect($summaryTotalsSource->items())
            : collect($summaryTotalsSource);
        $staffAppCutTotal = $summaryTotalsCollection
            ->sum(function ($summary) {
                $deductions = $summary['deductions'] ?? [];
                return $deductions['app_cut'] ?? 0;
            });
        $staffProjectedNet = ($stats['projected_net'] ?? 0) + $staffAppCutTotal;
        $formatHours = function ($hours) {
            $value = is_numeric($hours) ? (float) $hours : 0;
            if ($value < 0) {
                $value = 0;
            }
            $wholeHours = (int) floor($value);
            $minutes = (int) round(($value - $wholeHours) * 60);
            if ($minutes === 60) {
                $wholeHours += 1;
                $minutes = 0;
            }
            $parts = [];
            if ($wholeHours > 0 || $minutes === 0) {
                $parts[] = $wholeHours . ' ' . ($wholeHours === 1 ? 'hr' : 'hrs');
            }
            if ($minutes > 0) {
                $parts[] = $minutes . ' ' . ($minutes === 1 ? 'min' : 'mins');
            }
            return implode(' ', $parts);
        };
        $generatedByUser = auth()->guard('admin')->user();
        $generatedByName = $generatedByUser
            ? trim(($generatedByUser->first_name ?? '') . ' ' . ($generatedByUser->last_name ?? ''))
            : '';
        if ($generatedByName === '') {
            $generatedByName = optional($generatedByUser)->name ?? '—';
        }
        $employmentTypeLabel = function ($employmentTypeKey) {
            if ($employmentTypeKey === null || $employmentTypeKey === '') {
                return null;
            }
            return match ($employmentTypeKey) {
                'salaried' => 'Salaried (Basic Pay)',
                'contractor' => 'Contractor / Freelance',
                default => $employmentTypeKey,
            };
        };
    @endphp
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mb-4">
                <div
                    class="card border-0 shadow-sm rounded-4 text-white mt-4"
                    style="background: linear-gradient(120deg, #111827 0%, #1f2937 60%, #ef4444 120%);"
                >
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-white text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Payroll Run</span>
                                <h2 class="fw-bold mb-2">Process payroll</h2>
                                <p class="text-white-50 mb-3">
                                    Review hours, deductions, and payouts for {{ $monthLabel }} before finalizing your payroll.
                                </p>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('admin.payrolls.index') }}" class="btn btn-light d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-arrow-left"></i>
                                        Back to payroll list
                                    </a>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-danger text-white fw-semibold rounded-pill px-3 py-2">{{ $monthLabel }}</span>
                                <h3 class="display-6 fw-bold mb-1">₱{{ number_format($staffProjectedNet, 2) }}</h3>
                                <p class="text-white-50 mb-0">Projected payout for selected month</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3">
                <form action="{{ route('admin.payrolls.process') }}" method="GET" class="card shadow-sm border-0 rounded-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label text-muted text-uppercase small mb-1">Search staff</label>
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input
                                        type="search"
                                        name="search"
                                        value="{{ $search }}"
                                        class="form-control rounded-pill ps-5"
                                        placeholder="Search by name or email"
                                    />
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label text-muted text-uppercase small mb-1">Payroll month</label>
                                <input
                                    type="month"
                                    name="month"
                                    value="{{ $month }}"
                                    class="form-control rounded-pill"
                                    max="{{ now()->format('Y-m') }}"
                                />
                            </div>
                            <div class="col-12 col-md-4 d-flex justify-content-md-end gap-2">
                                <a href="{{ route('admin.payrolls.process') }}" class="btn btn-outline-secondary rounded-pill px-4">Reset</a>
                                <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-rotate"></i>
                                    Update view
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-12 mb-3">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">View mode</span>
                            <h5 class="fw-semibold mb-1 mb-lg-0">Switch between staff and trainer payrolls</h5>
                        </div>
                        <div class="btn-group" role="group" aria-label="Toggle payroll sections">
                            <button type="button" class="btn btn-outline-dark active" data-payroll-toggle="staff">Staff payroll</button>
                            <button type="button" class="btn btn-outline-dark" data-payroll-toggle="trainer">Trainer payroll</button>
                            <button type="button" class="btn btn-outline-dark" data-payroll-toggle="both">Show both</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-3 d-flex justify-content-end">
                <button
                    type="button"
                    class="btn btn-outline-secondary rounded-pill d-flex align-items-center gap-2"
                    data-bs-toggle="modal"
                    data-bs-target="#deductionModal"
                >
                    <i class="fa-solid fa-sliders"></i>
                    Adjust deductions
                </button>
            </div>

            <section id="staff-payroll-section" class="payroll-section mb-4">
            @php
                $staffSummariesSource = $summaries ?? collect();
                $staffSummariesCollection = $staffSummariesSource instanceof \Illuminate\Pagination\AbstractPaginator
                    ? collect($staffSummariesSource->items())
                    : collect($staffSummariesSource);
                $staffSummariesWithHours = $staffSummariesCollection->filter(function ($summary) {
                    return (float) ($summary['total_hours'] ?? 0) > 0;
                })->values();
            @endphp
            <div class="col-12 mb-2">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <span class="badge bg-dark text-white fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Staff payroll</span>
                        <h4 class="fw-semibold mb-0">Hourly staff payout review</h4>
                    </div>
                    <span class="text-muted small">Focused on Attendance2 clock-ins and clock-outs</span>
                </div>
            </div>
            <div class="col-12 mb-3">
                @if(session('success'))
                    <div class="alert alert-success rounded-4 shadow-sm border-0">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger rounded-4 shadow-sm border-0">
                        {{ session('error') }}
                    </div>
                @endif
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Staff in this run</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-user-group text-danger fs-4"></i>
                                    <span class="fs-4 fw-bold">{{ $stats['staff_count'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Total hours</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-clock-rotate-left text-primary fs-4"></i>
                                    <span class="fs-4 fw-bold">{{ $formatHours($stats['total_hours']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Total net payout</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-peso-sign text-success fs-4"></i>
                                    <span class="fs-4 fw-bold">₱{{ number_format($staffProjectedNet, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($staffSummariesWithHours->isNotEmpty())
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Staff</th>
                                        <th>Rate</th>
                                        <th>Hours</th>
                                        <th>Gross Pay</th>
                                        <th>Net Pay</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($staffSummariesWithHours as $summary)
                                        @php
                                            $staff = $summary['staff'];
                                            $modalId = 'staff-payroll-modal-' . $staff->id;
                                            $staffProcessedRun = $summary['processed_run'] ?? null;
                                            $staffProcessedTotals = $summary['processed_totals'] ?? [
                                                'count' => 0,
                                                'hours' => 0,
                                                'gross' => 0,
                                                'net' => 0,
                                                'sss' => 0,
                                                'philhealth' => 0,
                                                'pagibig' => 0,
                                                'app_cut' => 0,
                                                'last_processed_at' => null,
                                            ];
                                            $hasStaffProcessed = (int) ($staffProcessedTotals['count'] ?? 0) > 0;
                                            $hasStaffRemaining = ($summary['entries'] ?? collect())->count() > 0;
                                            $staffLastProcessedAt = $staffProcessedTotals['last_processed_at'] ?? null;
                                            $staffHasTin = !empty($staff->tin_number);
                                            $staffHasSss = $staffHasTin && !empty($staff->sss_number);
                                            $staffHasPhilhealth = $staffHasTin && !empty($staff->philhealth_number);
                                            $staffHasPagibig = $staffHasTin && !empty($staff->pagibig_number);
                                            $hasStaffData = ($summary['entries'] ?? collect())->count() > 0;
                                            $staffNoData = !$hasStaffRemaining;
                                            $staffDeductions = $summary['deductions'] ?? [];
                                            $staffNetWithoutAppCut = max(
                                                round(
                                                    ($summary['gross_pay'] ?? 0)
                                                    - (($staffDeductions['sss'] ?? 0) + ($staffDeductions['philhealth'] ?? 0) + ($staffDeductions['pagibig'] ?? 0)),
                                                    2
                                                ),
                                                0
                                            );
                                            $staffDeductionsForDisplay = array_merge($staffDeductions, ['app_cut' => 0]);
                                            $staffTotalDeductions = ($staffDeductionsForDisplay['sss'] ?? 0)
                                                + ($staffDeductionsForDisplay['philhealth'] ?? 0)
                                                + ($staffDeductionsForDisplay['pagibig'] ?? 0)
                                                + ($staffDeductionsForDisplay['app_cut'] ?? 0);
                                        @endphp
                                        <tr
                                            data-payroll-card
                                            data-modal-id="{{ $modalId }}"
                                            data-has-tin="{{ $staffHasTin ? '1' : '0' }}"
                                            data-has-sss="{{ $staffHasSss ? '1' : '0' }}"
                                            data-has-philhealth="{{ $staffHasPhilhealth ? '1' : '0' }}"
                                            data-has-pagibig="{{ $staffHasPagibig ? '1' : '0' }}"
                                            data-gross="{{ $summary['gross_pay'] }}"
                                            data-rate="{{ $staff->rate_per_hour ?? 0 }}"
                                            data-appcut="0"
                                        >
                                            <td>
                                                <div class="fw-semibold">{{ $staff->first_name }} {{ $staff->last_name }}</div>
                                                <div class="text-muted small">{{ $staff->email }}</div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark fw-semibold rounded-pill px-3 py-2">
                                                    ₱{{ number_format($staff->rate_per_hour ?? 0, 2) }} / hr
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $formatHours($summary['total_hours']) }}</div>
                                                @if($hasStaffProcessed)
                                                    <div class="text-muted small">
                                                        Processed ({{ $staffProcessedTotals['count'] ?? 0 }} run{{ ($staffProcessedTotals['count'] ?? 0) === 1 ? '' : 's' }}):
                                                        {{ $formatHours($staffProcessedTotals['hours'] ?? 0) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-semibold">₱{{ number_format($summary['gross_pay'], 2) }}</div>
                                                @if($hasStaffProcessed)
                                                    <div class="text-muted small">Processed: ₱{{ number_format((float) ($staffProcessedTotals['gross'] ?? 0), 2) }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-success" data-net>₱{{ number_format($staffNetWithoutAppCut, 2) }}</div>
                                                @if($hasStaffProcessed)
                                                    <div class="text-muted small">Processed: ₱{{ number_format((float) ($staffProcessedTotals['net'] ?? 0), 2) }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                                                    @php
                                                        $staffProcessDisabled = $summary['pending_entries'] || $staffNoData;
                                                        $staffProcessTitle = $summary['pending_entries']
                                                            ? 'Clock-out pending entries before processing'
                                                            : ($staffNoData
                                                                ? ($hasStaffProcessed ? 'All entries already processed for this period' : 'Processing disabled: no attendance data yet')
                                                                : 'Process and save payroll');
                                                        $rangeEntries = $summary['entries']->map(function ($entry) use ($formatHours) {
                                                            $clockIn = $entry['clockin_at'] ?? null;
                                                            $clockOut = $entry['clockout_at'] ?? null;
                                                            $dateSource = $clockIn ?: $clockOut;
                                                            $dateLabel = $dateSource ? $dateSource->format('M j, Y') : '—';
                                                            $day = $dateSource ? $dateSource->day : null;
                                                            $timeRange = '—';
                                                            if ($clockIn && $clockOut) {
                                                                $timeRange = $clockIn->format('g:i A') . ' - ' . $clockOut->format('g:i A');
                                                            } elseif ($clockIn) {
                                                                $timeRange = $clockIn->format('g:i A') . ' - —';
                                                            }
                                                            $hours = $entry['hours'] ?? null;
                                                            $metaParts = [];
                                                            if ($timeRange !== '—') {
                                                                $metaParts[] = $timeRange;
                                                            }
                                                            if (!is_null($hours)) {
                                                                $metaParts[] = $formatHours($hours);
                                                            }
                                                            $meta = implode(' • ', $metaParts);
                                                            $isComplete = ($entry['status'] ?? '') === 'complete';
                                                            $title = 'Attendance entry';
                                                            if (!empty($entry['id'])) {
                                                                $title .= ' #' . $entry['id'];
                                                            }

                                                            return [
                                                                'type' => 'staff_entry',
                                                                'title' => $title,
                                                                'meta' => $meta !== '' ? $meta : null,
                                                                'timeline' => [
                                                                    [
                                                                        'label' => $dateLabel,
                                                                        'day' => $day,
                                                                        'status' => $isComplete ? 'Completed (paid)' : 'Pending',
                                                                        'payable' => $isComplete ? 1 : 0,
                                                                    ],
                                                                ],
                                                                'amounts' => [
                                                                    'completed' => (float) ($entry['amount'] ?? 0),
                                                                ],
                                                            ];
                                                        })->values();
                                                        $rangeEntriesJson = json_encode($rangeEntries, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT);
                                                    @endphp
                                                    <form action="{{ route('admin.payrolls.process-staff') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="staff_id" value="{{ $staff->id }}">
                                                        <input type="hidden" name="month" value="{{ $month }}">
                                                        <button
                                                            type="submit"
                                                            class="btn btn-success rounded-pill px-3 d-flex align-items-center gap-2 process-payroll-btn"
                                                            data-base-disabled="{{ $staffProcessDisabled ? '1' : '0' }}"
                                                            data-base-title="{{ $staffProcessTitle }}"
                                                            data-range-entries='{{ $rangeEntriesJson }}'
                                                            data-role="staff"
                                                            data-name="{{ $staff->first_name }} {{ $staff->last_name }}"
                                                            data-month="{{ $monthLabel }}"
                                                            data-hours="{{ number_format($summary['total_hours'], 2, '.', '') }}"
                                                            data-gross="{{ number_format($summary['gross_pay'], 2, '.', '') }}"
                                                            data-net="{{ number_format($staffNetWithoutAppCut, 2, '.', '') }}"
                                                            data-pending="{{ (int) $summary['pending_entries'] }}"
                                                            data-basis="Attendance clock-ins/outs"
                                                            {{ $staffProcessDisabled ? 'disabled' : '' }}
                                                            title="{{ $staffProcessTitle }}"
                                                        >
                                                            <i class="fa-solid fa-circle-check"></i>
                                                            {{ $staffProcessDisabled ? ($hasStaffProcessed ? 'Processed' : 'Process payroll') : 'Process payroll' }}
                                                        </button>
                                                    </form>
                                                    @php
                                                        $printEntries = $summary['entries']->map(function ($entry) {
                                                            return [
                                                                'id' => $entry['id'],
                                                                'clockin' => $entry['clockin_at'] ? $entry['clockin_at']->format('M d, Y g:i A') : '—',
                                                                'clockout' => $entry['clockout_at'] ? $entry['clockout_at']->format('M d, Y g:i A') : '—',
                                                                'hours' => $entry['hours'],
                                                                'amount' => $entry['amount'],
                                                                'status' => $entry['status'],
                                                            ];
                                                        })->values();
                                                        $staffEmploymentTypeLabel = $employmentTypeLabel($staff->employment_type ?? null);
                                                        $staffHours = (float) ($summary['total_hours'] ?? 0);
                                                        $staffRate = $staffHours > 0
                                                            ? round((float) ($summary['gross_pay'] ?? 0) / max($staffHours, 0.01), 2)
                                                            : null;

                                                        $payslipData = [
                                                            'type' => 'staff',
                                                            'name' => $staff->first_name . ' ' . $staff->last_name,
                                                            'email' => $staff->email,
                                                            'rate' => $staffRate,
                                                            'hours' => $staffHours,
                                                            'gross' => $summary['gross_pay'],
                                                            'net' => $staffNetWithoutAppCut,
                                                            'deductions' => $staffDeductionsForDisplay,
                                                            'employment_type' => $staffEmploymentTypeLabel,
                                                            'generated_by' => $generatedByName,
                                                            'generated_at' => now()->format('M d, Y g:i A'),
                                                            'month' => $monthLabel,
                                                            'entries' => $printEntries,
                                                        ];

                                                        $payslipJson = json_encode($payslipData);
                                                    @endphp
                                                    <button
                                                        class="btn btn-outline-primary rounded-pill px-3"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#{{ $modalId }}"
                                                    >
                                                        Review details
                                                    </button>
                                                </div>
                                                @if($staffNoData && !$hasStaffProcessed)
                                                    <div class="text-muted small mt-1 text-start">Process is disabled because there is no attendance data yet.</div>
                                                @elseif($staffNoData && $hasStaffProcessed)
                                                    <div class="text-muted small mt-1 text-start">All attendance entries in this period have already been processed.</div>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr class="d-none">
                                            <td colspan="6">
<div class="modal fade staff-payroll-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content rounded-4 border-0 shadow-sm">
                                <div class="modal-header border-0 pb-0">
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap align-items-start gap-2">
                                            <div>
                                                <h5 class="modal-title fw-semibold mb-1" id="{{ $modalId }}Label">Payroll Summary - {{ $staff->first_name }} {{ $staff->last_name }}</h5>
                                                <div class="text-muted small">Payroll Period: {{ $monthLabel }}</div>
                                            </div>
                                            <div class="ms-auto text-end">
                                                @if(!$hasStaffRemaining && $hasStaffProcessed)
                                                    <span class="text-muted small">Payroll locked on {{ $staffLastProcessedAt ? $staffLastProcessedAt->format('M d, Y') : '—' }} - edits disabled</span>
                                                @else
                                                    <span class="text-muted small">Payroll open - edits enabled</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body pt-2">
                                    <style>
                                        .staff-payroll-modal .modal-body { background: #f8fafc; }
                                        .staff-payroll-modal .payroll-summary-card {
                                            border: 1px solid #e5e7eb;
                                            border-radius: 18px;
                                            background: #fff;
                                            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
                                        }
                                        .staff-payroll-modal .summary-grid {
                                            display: grid;
                                            grid-template-columns: repeat(4, minmax(0, 1fr));
                                        }
                                        .staff-payroll-modal .summary-item { padding: 14px 18px; }
                                        .staff-payroll-modal .summary-item + .summary-item { border-left: 1px solid #e5e7eb; }
                                        .staff-payroll-modal .summary-label {
                                            font-size: 12px;
                                            text-transform: uppercase;
                                            letter-spacing: .04em;
                                            color: #6b7280;
                                        }
                                        .staff-payroll-modal .summary-value { font-size: 20px; font-weight: 700; color: #111827; }
                                        .staff-payroll-modal .status-pill {
                                            padding: 6px 12px;
                                            border-radius: 999px;
                                            font-size: 12px;
                                            font-weight: 600;
                                            display: inline-flex;
                                            align-items: center;
                                            gap: 6px;
                                        }
                                        .staff-payroll-modal .payroll-card {
                                            border: 1px solid #e5e7eb;
                                            border-radius: 16px;
                                            overflow: hidden;
                                            background: #fff;
                                        }
                                        .staff-payroll-modal .payroll-card-toggle { background: #f8fafc; border: 0; }
                                        .staff-payroll-modal .payroll-card-toggle:focus { box-shadow: none; }
                                        .staff-payroll-modal .payroll-icon {
                                            width: 32px;
                                            height: 32px;
                                            border-radius: 10px;
                                            background: #eef2ff;
                                            color: #4338ca;
                                            display: inline-flex;
                                            align-items: center;
                                            justify-content: center;
                                        }
                                        .staff-payroll-modal .filter-card {
                                            border: 1px solid #e5e7eb;
                                            border-radius: 16px;
                                            background: #fff;
                                        }
                                        .staff-payroll-modal .payroll-table {
                                            border: 1px solid #e5e7eb;
                                            border-radius: 16px;
                                            overflow: hidden;
                                            background: #fff;
                                        }
                                        .staff-payroll-modal .payroll-table thead th {
                                            background: #f8fafc;
                                            font-size: 12px;
                                            text-transform: uppercase;
                                            letter-spacing: .04em;
                                            color: #6b7280;
                                        }
                                        .staff-payroll-modal .entry-row { cursor: default; }
                                        .staff-payroll-modal .entries-select {
                                            min-width: 64px;
                                            padding-right: 28px;
                                            text-align: center;
                                        }
                                        @media (max-width: 991.98px) {
                                            .staff-payroll-modal .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                                            .staff-payroll-modal .summary-item { border-left: 0; border-top: 1px solid #e5e7eb; }
                                            .staff-payroll-modal .summary-item:nth-child(1),
                                            .staff-payroll-modal .summary-item:nth-child(2) { border-top: 0; }
                                            .staff-payroll-modal .summary-item:nth-child(odd) { border-right: 1px solid #e5e7eb; }
                                        }
                                        @media (max-width: 575.98px) {
                                            .staff-payroll-modal .summary-grid { grid-template-columns: minmax(0, 1fr); }
                                            .staff-payroll-modal .summary-item { border-right: 0; }
                                        }
                                    </style>

                                    <div class="payroll-summary-card mb-3">
                                        <div class="summary-grid">
                                            <div class="summary-item">
                                                <div class="summary-label">Gross Pay</div>
                                                <div class="summary-value">₱{{ number_format((float) $summary['gross_pay'], 2) }}</div>
                                            </div>
                                            <div class="summary-item">
                                                <div class="summary-label">Total Deductions</div>
                                                <div class="summary-value" data-total-deductions>₱{{ number_format((float) $staffTotalDeductions, 2) }}</div>
                                            </div>
                                            <div class="summary-item">
                                                <div class="summary-label">Net Pay</div>
                                                <div class="summary-value text-success" data-net>₱{{ number_format((float) $staffNetWithoutAppCut, 2) }}</div>
                                            </div>
                                            <div class="summary-item">
                                                <div class="summary-label">Status</div>
                                                @if($hasStaffRemaining && $summary['pending_entries'])
                                                    <span class="badge status-pill bg-warning-subtle text-warning"><i class="fa-solid fa-clock"></i> Pending</span>
                                                @elseif($hasStaffRemaining)
                                                    <span class="badge status-pill bg-success-subtle text-success"><i class="fa-solid fa-circle-check"></i> Ready</span>
                                                @elseif($hasStaffProcessed)
                                                    <span class="badge status-pill bg-success-subtle text-success"><i class="fa-solid fa-circle-check"></i> Processed</span>
                                                @else
                                                    <span class="badge status-pill bg-warning-subtle text-warning"><i class="fa-solid fa-clock"></i> Pending</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card payroll-card mb-3">
                                        <button
                                            class="btn payroll-card-toggle w-100 text-start d-flex align-items-center justify-content-between"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#{{ $modalId }}-deductions"
                                            aria-expanded="true"
                                            aria-controls="{{ $modalId }}-deductions"
                                        >
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="payroll-icon"><i class="fa-solid fa-chart-column"></i></span>
                                                <span class="fw-semibold">Deductions Breakdown</span>
                                            </div>
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </button>
                                        <div id="{{ $modalId }}-deductions" class="collapse show">
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <div class="text-muted small text-uppercase fw-semibold mb-2">Deductions</div>
                                                        <ul class="list-unstyled small mb-0">
                                                            @if($staffHasSss)
                                                                <li class="d-flex justify-content-between">
                                                                    <span>SSS</span>
                                                                    <span data-sss>₱{{ number_format($staffDeductionsForDisplay['sss'] ?? 0, 2) }}</span>
                                                                </li>
                                                            @endif
                                                            @if($staffHasPhilhealth)
                                                                <li class="d-flex justify-content-between">
                                                                    <span>PhilHealth</span>
                                                                    <span data-philhealth>₱{{ number_format($staffDeductionsForDisplay['philhealth'] ?? 0, 2) }}</span>
                                                                </li>
                                                            @endif
                                                            @if($staffHasPagibig)
                                                                <li class="d-flex justify-content-between">
                                                                    <span>Pag-IBIG</span>
                                                                    <span data-pagibig>₱{{ number_format($staffDeductionsForDisplay['pagibig'] ?? 0, 2) }}</span>
                                                                </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="filter-card p-3 mb-3">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-12 col-md-4">
                                                <label class="form-label text-muted text-uppercase small mb-1">Month</label>
                                                <input type="month" class="form-control form-control-sm" value="{{ $month }}" data-staff-filter-month>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label class="form-label text-muted text-uppercase small mb-1">Status</label>
                                                <select class="form-select form-select-sm" data-staff-filter-select>
                                                    <option value="all">All</option>
                                                    <option value="complete">Complete</option>
                                                    <option value="pending">Pending</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-4 d-flex align-items-end gap-2"></div>
                                        </div>
                                    </div>

                                    <div class="assignment-list">
                                        <div class="table-responsive payroll-table">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Entry ID</th>
                                                        <th>Clock in</th>
                                                        <th>Clock out</th>
                                                        <th class="text-end">Hours</th>
                                                        <th class="text-end">Amount</th>
                                                        <th class="text-center">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($summary['entries'] as $entry)
                                                        @php
                                                            $entryDate = $entry['clockin_at']
                                                                ? $entry['clockin_at']->toDateString()
                                                                : ($entry['clockout_at'] ? $entry['clockout_at']->toDateString() : '');
                                                            $entryStatus = $entry['status'] ?? 'pending';
                                                        @endphp
                                                        <tr
                                                            class="entry-row"
                                                            data-entry-row
                                                            data-entry-status="{{ $entryStatus }}"
                                                            data-entry-date="{{ $entryDate }}"
                                                        >
                                                            <td class="text-muted">#{{ $entry['id'] }}</td>
                                                            <td>
                                                                {{ $entry['clockin_at'] ? $entry['clockin_at']->format('M d, Y g:i A') : '—' }}
                                                            </td>
                                                            <td>
                                                                {{ $entry['clockout_at'] ? $entry['clockout_at']->format('M d, Y g:i A') : '—' }}
                                                            </td>
                                                            <td class="text-end">{{ !is_null($entry['hours']) ? $formatHours($entry['hours']) : 'Pending' }}</td>
                                                            <td class="text-end">
                                                                {{ $entry['amount'] ? '₱' . number_format($entry['amount'], 2) : '—' }}
                                                            </td>
                                                            <td class="text-center">
                                                                @if ($entry['status'] === 'complete')
                                                                    <span class="badge bg-success-subtle text-success fw-semibold rounded-pill px-3 py-2">Complete</span>
                                                                @else
                                                                    <span class="badge bg-warning-subtle text-warning fw-semibold rounded-pill px-3 py-2">Awaiting clock-out</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="text-center text-muted py-3">
                                                                No payroll entries available for this staff in {{ $monthLabel }}.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                    @if(($summary['entries'] ?? collect())->count() > 0)
                                                        <tr class="text-center text-muted d-none" data-staff-filter-empty>
                                                            <td colspan="6" class="py-3">No entries match this filter.</td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                                        <div class="d-flex flex-wrap align-items-center gap-2 text-muted small">
                                            <span class="text-uppercase fw-semibold">Entries per page:</span>
                                            <select class="form-select form-select-sm w-auto entries-select" disabled>
                                                <option selected>10</option>
                                            </select>
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Pagination">
                                                <button class="btn btn-outline-secondary" type="button" disabled>
                                                    <i class="fa-solid fa-chevron-left"></i>
                                                </button>
                                                <span class="btn btn-outline-secondary disabled">1 of 1</span>
                                                <button class="btn btn-outline-secondary" type="button" disabled>
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" class="btn btn-danger btn-sm payslip-btn" data-payslip='{{ $payslipJson }}'>
                                                <i class="fa-solid fa-print"></i>
                                                Print
                                            </button>
                                            <button type="button" class="btn btn-success btn-sm" data-bs-dismiss="modal">
                                                <i class="fa-solid fa-xmark"></i>
                                                Close
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if($summaries instanceof \Illuminate\Pagination\AbstractPaginator)
                                {{ $summaries->links() }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @else
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body text-center py-5">
                            <h5 class="fw-semibold mb-2">No staff payroll data found</h5>
                            <p class="text-muted mb-3">Try selecting a different month or adjusting your search filters.</p>
                            <a href="{{ route('admin.payrolls.index') }}" class="btn btn-danger rounded-pill px-4">Go back to payroll list</a>
                        </div>
                    </div>
                </div>
            @endif
            </section>

            <section id="trainer-payroll-section" class="payroll-section">
            @php
                $trainerAssignmentsSource = $trainerAssignments ?? collect();
                $trainerAssignmentsCollection = $trainerAssignmentsSource instanceof \Illuminate\Pagination\AbstractPaginator
                    ? collect($trainerAssignmentsSource->items())
                    : collect($trainerAssignmentsSource);
                $trainerAssignmentsWithHours = $trainerAssignmentsCollection->filter(function ($assignment) {
                    return (float) ($assignment['total_hours'] ?? 0) > 0;
                })->values();
                $trainerStats = $trainerStats ?? [
                    'trainer_count' => $trainerAssignmentsWithHours->count(),
                    'payable_classes' => $trainerAssignmentsWithHours->sum(function ($assignment) {
                        return (int) ($assignment['payable_assignments_count'] ?? 0);
                    }),
                    'total_hours' => $trainerAssignmentsWithHours->sum(function ($assignment) {
                        return (float) ($assignment['total_hours'] ?? 0);
                    }),
                    'projected_net' => $trainerAssignmentsWithHours->sum(function ($assignment) {
                        return (float) ($assignment['net_pay'] ?? 0);
                    }),
                ];
            @endphp
            <div class="col-12 mb-2">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <span class="badge bg-dark text-white fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Trainer payroll</span>
                        <h4 class="fw-semibold mb-0">Trainer payout review</h4>
                    </div>
                    <span class="text-muted small">Focused on classes with attendance</span>
                </div>
            </div>
            <div class="col-12 mb-3">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Trainers in this run</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-user-group text-danger fs-4"></i>
                                    <span class="fs-4 fw-bold">{{ $trainerStats['trainer_count'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Payable classes</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-calendar-check text-warning fs-4"></i>
                                    <span class="fs-4 fw-bold">{{ $trainerStats['payable_classes'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Total hours</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-clock-rotate-left text-primary fs-4"></i>
                                    <span class="fs-4 fw-bold">{{ $formatHours($trainerStats['total_hours']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Total net payout</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-peso-sign text-success fs-4"></i>
                                    <span class="fs-4 fw-bold">₱{{ number_format($trainerStats['projected_net'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($trainerAssignmentsWithHours->isNotEmpty())
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Trainer</th>
                                        <th>Payable classes</th>
                                        <th>Hours</th>
                                        <th>Gross Pay</th>
                                        <th>Net Pay</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trainerAssignmentsWithHours as $assignment)
                                        @php
                                $trainer = $assignment['trainer'];
                                $modalId = 'trainer-assignments-' . $trainer->id;
                                $trainerHasTin = !empty($trainer->tin_number);
                                $trainerHasSss = $trainerHasTin && !empty($trainer->sss_number);
                                $trainerHasPhilhealth = $trainerHasTin && !empty($trainer->philhealth_number);
                                $trainerHasPagibig = $trainerHasTin && !empty($trainer->pagibig_number);
                                $totals = $assignment['totals'];
                                $processedRun = $assignment['processed_run'] ?? null;
                                $processedTotals = $assignment['processed_totals'] ?? [
                                    'count' => 0,
                                    'hours' => 0,
                                    'gross' => 0,
                                    'net' => 0,
                                    'sss' => 0,
                                    'philhealth' => 0,
                                    'pagibig' => 0,
                                    'app_cut' => 0,
                                    'last_processed_at' => null,
                                ];
                                $hasProcessed = (int) ($processedTotals['count'] ?? 0) > 0;
                                $hasRemaining = (int) ($assignment['payable_assignments_count'] ?? 0) > 0;
                                $lastProcessedAt = $processedTotals['last_processed_at'] ?? null;
                                $trainerGross = $assignment['payable_salary'] ?? 0;
                                $trainerProjectedGross = $assignment['total_salary'] ?? 0;
                                $trainerUpcoming = $totals['future_total'] ?? 0;
                                $trainerSss = $assignment['deductions']['sss'] ?? round($trainerGross * 0.045, 2);
                                $trainerPhilhealth = $assignment['deductions']['philhealth'] ?? round($trainerGross * 0.025, 2);
                                $trainerPagibig = $assignment['deductions']['pagibig'] ?? round(min($trainerGross, 5000) * 0.02, 2);
                                $trainerAppCut = $assignment['deductions']['app_cut'] ?? 0;
                                $trainerTotalDeductions = round($trainerSss + $trainerPhilhealth + $trainerPagibig + $trainerAppCut, 2);
                                $trainerNet = $assignment['net_pay'] ?? 0;
                                $displayProjectedGross = (float) $trainerProjectedGross;
                                $displayNet = (float) $trainerNet;
                                $assignmentDetails = collect($assignment['details'] ?? collect())
                                    ->filter(function ($detail) {
                                        return ($detail['salary_eligible'] ?? false) && ($detail['in_month'] ?? false);
                                    })
                                    ->sortBy(function ($detail) {
                                        $start = $detail['start'] ?? null;
                                        return $start instanceof \Carbon\CarbonInterface
                                            ? $start->getTimestamp()
                                            : PHP_INT_MAX;
                                    })
                                    ->values();
                                $processedSeries = collect($assignment['processed_series'] ?? []);
                                $processedLabel = $lastProcessedAt instanceof \Carbon\CarbonInterface
                                    ? 'Processed ' . $lastProcessedAt->format('M d, Y g:i A')
                                    : 'Processed';
                                $attendanceAssignments = $assignmentDetails
                                    ->filter(function ($detail) {
                                        $att = collect($detail['attendances'] ?? []);
                                        return $att->isNotEmpty() || ($detail['has_attendance'] ?? false);
                                    })
                                    ->map(function ($detail) {
                                        $schedule = $detail['schedule'];
                                        $start = $detail['start'];
                                        $end = $detail['end'];
                                        $paidDates = collect($detail['paid_dates'] ?? $detail['occurrence_dates'] ?? collect())->map(function ($date) {
                                            try {
                                                return \Carbon\Carbon::parse($date)->format('M d, Y');
                                            } catch (\Throwable $th) {
                                                return $date;
                                            }
                                        })->filter()->values();
                                        $dateList = $paidDates->isNotEmpty()
                                            ? $paidDates
                                            : collect([$start ? $start->format('M d, Y') : '—']);
                                        $attendance = collect($detail['attendances'] ?? collect())->map(function ($record) {
                                            $clockIn = $record['clockin_at'] ?? null;
                                            $clockOut = $record['clockout_at'] ?? null;

                                            $label = '';
                                            if ($clockIn) {
                                                $label .= $clockIn->format('g:i A');
                                            }

                                            if ($clockOut) {
                                                $label .= $label !== '' ? ' - ' . $clockOut->format('g:i A') : $clockOut->format('g:i A');
                                            }

                                            return $label !== '' ? $label : 'Attendance recorded';
                                        })->filter()->values();
                                        return [
                                            'title' => $schedule->name ?? 'Class schedule',
                                            'code' => $schedule->class_code ?? ($schedule->id ?? 'N/A'),
                                            'rate' => $schedule->trainer_rate_per_hour ?? null,
                                            'dates' => $dateList->values(),
                                            'date' => $dateList->implode(', '),
                                            'time' => $detail['time_range'] ?? ($start || $end
                                                ? trim(($start ? $start->format('g:i A') : '') . ($end ? ' - ' . $end->format('g:i A') : ''))
                                                : '—'),
                                            'hours' => $detail['payroll_hours'] ?? $detail['hours'] ?? 0,
                                            'scheduled_hours' => $detail['hours'] ?? 0,
                                            'salary' => $detail['payroll_salary'] ?? $detail['summary_salary'] ?? $detail['display_salary'] ?? 0,
                                            'attendance' => $attendance,
                                            'recurrence' => $detail['recurring_label'] ?? '',
                                            'status' => ($detail['has_attendance'] ?? false) ? 'Present' : 'Absent',
                                        ];
                                    })->values();
                                $trainerPayslipData = [
                                    'type' => 'trainer',
                                    'name' => $trainer->first_name . ' ' . $trainer->last_name,
                                    'email' => $trainer->email,
                                    'rate' => ($assignment['total_hours'] ?? 0) > 0
                                        ? round((float) $trainerGross / max((float) ($assignment['total_hours'] ?? 0), 0.01), 2)
                                        : null,
                                    'hours' => (float) ($assignment['total_hours'] ?? 0),
                                    'gross' => $trainerGross,
                                    'net' => $trainerNet,
                                    'deductions' => [
                                        'sss' => $trainerSss,
                                        'philhealth' => $trainerPhilhealth,
                                        'pagibig' => $trainerPagibig,
                                        'app_cut' => $trainerAppCut,
                                    ],
                                    'employment_type' => $employmentTypeLabel($trainer->employment_type ?? null),
                                    'generated_by' => $generatedByName,
                                    'generated_at' => now()->format('M d, Y g:i A'),
                                    'month' => $monthLabel,
                                    'assignments' => $attendanceAssignments,
                                ];
                                $trainerPayslipJson = json_encode($trainerPayslipData);
                                $trainerNoData = !$hasRemaining;
                                $canProcessTrainer = $hasRemaining;
                            @endphp
                                        <tr
                                            data-trainer-card
                                            data-modal-id="{{ $modalId }}"
                                            data-has-tin="{{ $trainerHasTin ? '1' : '0' }}"
                                            data-has-sss="{{ $trainerHasSss ? '1' : '0' }}"
                                            data-has-philhealth="{{ $trainerHasPhilhealth ? '1' : '0' }}"
                                            data-has-pagibig="{{ $trainerHasPagibig ? '1' : '0' }}"
                                            data-gross="{{ $trainerGross }}"
                                            data-sss="{{ $trainerSss }}"
                                            data-philhealth="{{ $trainerPhilhealth }}"
                                            data-pagibig="{{ $trainerPagibig }}"
                                            data-appcut="{{ $trainerAppCut }}"
                                            data-net="{{ $displayNet }}"
                                        >
                                            <td>
                                                <div class="fw-semibold">{{ $trainer->first_name }} {{ $trainer->last_name }}</div>
                                                <div class="text-muted small">{{ $trainer->email }}</div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark fw-semibold rounded-pill px-3 py-2">
                                                    {{ $assignment['payable_assignments_count'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $formatHours($assignment['total_hours'] ?? 0) }}</div>
                                                @if($hasProcessed)
                                                    <div class="text-muted small">
                                                        Processed ({{ $processedTotals['count'] ?? 0 }} run{{ ($processedTotals['count'] ?? 0) === 1 ? '' : 's' }}):
                                                        {{ $formatHours($processedTotals['hours'] ?? 0) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-semibold">₱{{ number_format($trainerGross, 2) }}</div>
                                                @if($hasProcessed)
                                                    <div class="text-muted small">Processed: ₱{{ number_format((float) ($processedTotals['gross'] ?? 0), 2) }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-success" data-net>₱{{ number_format($displayNet, 2) }}</div>
                                                @if($hasProcessed)
                                                    <div class="text-muted small">Processed: ₱{{ number_format((float) ($processedTotals['net'] ?? 0), 2) }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                                                    @php
                                                        $trainerProcessDisabled = !$canProcessTrainer;
                                                        $trainerProcessTitle = $canProcessTrainer
                                                            ? 'Process and save payroll'
                                                            : ($hasProcessed
                                                                ? 'All assignments already processed for this period'
                                                                : 'Processing disabled: no completed assignments for this period');
                                                    @endphp
                                                    <form action="{{ route('admin.payrolls.process-trainer') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="trainer_id" value="{{ $trainer->id }}">
                                                        <input type="hidden" name="month" value="{{ $month }}">
                                                        @php
                                                            $rangeAssignments = $assignmentDetails->map(function ($detail) {
                                                                $timeline = collect($detail['occurrence_dates'] ?? [])->map(function ($date) use ($detail) {
                                                                    try {
                                                                        $parsed = \Carbon\Carbon::parse($date);
                                                                    } catch (\Throwable $th) {
                                                                        return null;
                                                                    }
                                                                    $dateKey = $parsed->toDateString();
                                                                    $isPaid = collect($detail['paid_dates'] ?? [])->contains($dateKey);
                                                                    $isPast = collect($detail['past_dates'] ?? [])->contains($dateKey);
                                                                    $isFuture = collect($detail['future_dates'] ?? [])->contains($dateKey);
                                                                    $status = $isFuture ? 'Upcoming' : ($isPaid ? 'Completed (paid)' : ($isPast ? 'Completed' : '—'));

                                                                    return [
                                                                        'label' => $parsed->format('M j, Y'),
                                                                        'day' => $parsed->day,
                                                                        'status' => $status,
                                                                        'payable' => $isPaid ? 1 : 0,
                                                                    ];
                                                                })->filter()->values();

                                                                return [
                                                                    'title' => $detail['schedule']->name ?? 'Unnamed Schedule',
                                                                    'code' => $detail['schedule']->class_code ?? null,
                                                                    'timeline' => $timeline,
                                                                    'amounts' => [
                                                                        'upcoming' => $detail['future_potential_salary'] ?? $detail['display_salary'] ?? 0,
                                                                        'completed' => $detail['payroll_salary'] ?? 0,
                                                                    ],
                                                                ];
                                                            })->values();
                                                            $rangeAssignmentsJson = json_encode($rangeAssignments, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT);
                                                        @endphp
                                                        <button
                                                            type="submit"
                                                            class="btn btn-success rounded-pill px-3 d-flex align-items-center gap-2 process-payroll-btn"
                                                            data-base-disabled="{{ $trainerProcessDisabled ? '1' : '0' }}"
                                                            data-base-title="{{ $trainerProcessTitle }}"
                                                            data-range-assignments='{{ $rangeAssignmentsJson }}'
                                                            data-role="trainer"
                                                            data-name="{{ $trainer->first_name }} {{ $trainer->last_name }}"
                                                            data-month="{{ $monthLabel }}"
                                                            data-hours="{{ number_format($assignment['total_hours'] ?? 0, 2, '.', '') }}"
                                                            data-gross="{{ number_format($trainerGross, 2, '.', '') }}"
                                                            data-net="{{ number_format($displayNet, 2, '.', '') }}"
                                                            data-pending="0"
                                                            data-basis="Classes with attendance"
                                                            {{ $trainerProcessDisabled ? 'disabled' : '' }}
                                                            title="{{ $trainerProcessTitle }}"
                                                        >
                                                            <i class="fa-solid fa-circle-check"></i>
                                                            {{ $hasRemaining ? 'Process payroll' : ($hasProcessed ? 'Processed' : 'Process payroll') }}
                                                        </button>
                                                    </form>
                                                    <button
                                                        class="btn btn-outline-primary rounded-pill px-3"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#{{ $modalId }}"
                                                    >
                                                        Review details
                                                    </button>
                                                </div>
                                                @if($trainerNoData && empty($processedRun))
                                                    <div class="text-muted small mt-1 text-start">Process is disabled because there is no class data with attendance yet.</div>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr class="d-none">
                                            <td colspan="6">
<div class="modal fade assignment-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
                                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <div class="flex-grow-1">
                                                <div class="d-flex flex-wrap align-items-start gap-2">
                                                    <div>
                                                        <h5 class="modal-title fw-semibold mb-1" id="{{ $modalId }}Label">Payroll Summary - {{ $trainer->first_name }} {{ $trainer->last_name }}</h5>
                                                        <div class="text-muted small">Payroll Period: {{ $monthLabel }}</div>
                                                    </div>
                                                    <div class="ms-auto text-end">
                                                        @if(!$hasRemaining && $hasProcessed)
                                                            <span class="text-muted small">Payroll locked on {{ $lastProcessedAt ? $lastProcessedAt->format('M d, Y') : '—' }} - edits disabled</span>
                                                        @else
                                                            <span class="text-muted small">Payroll open - edits enabled</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body pt-2">
    <style>
        .assignment-modal .modal-body { background: #f8fafc; }
        .assignment-modal .payroll-summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .assignment-modal .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        .assignment-modal .summary-item { padding: 14px 18px; }
        .assignment-modal .summary-item + .summary-item { border-left: 1px solid #e5e7eb; }
        .assignment-modal .summary-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #6b7280;
        }
        .assignment-modal .summary-value { font-size: 20px; font-weight: 700; color: #111827; }
        .assignment-modal .status-pill {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .assignment-modal .payroll-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
        }
        .assignment-modal .payroll-card-toggle { background: #f8fafc; border: 0; }
        .assignment-modal .payroll-card-toggle:focus { box-shadow: none; }
        .assignment-modal .payroll-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: #eef2ff;
            color: #4338ca;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .assignment-modal .filter-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
        }
        .assignment-modal .payroll-table {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
        }
        .assignment-modal .payroll-table thead th {
            background: #f8fafc;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #6b7280;
        }
        .assignment-modal .assignment-row { cursor: pointer; }
        .assignment-modal .assignment-row.is-selected { background: #eef2ff; }
        .assignment-modal .assignment-date-range { display: none; }
        .assignment-modal [data-date-display="range"] .assignment-date-list { display: none; }
        .assignment-modal [data-date-display="range"] .assignment-date-range { display: block; }
        .assignment-modal .assignment-date-toggle {
            font-size: 12px;
            color: #2563eb;
            text-decoration: underline;
            background: none;
            border: 0;
            padding: 0;
        }
        .assignment-modal .assignment-date-toggle:hover { color: #1d4ed8; }
        .assignment-modal .entries-select {
            min-width: 64px;
            padding-right: 28px;
            text-align: center;
        }
        @media (max-width: 991.98px) {
            .assignment-modal .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .assignment-modal .summary-item { border-left: 0; border-top: 1px solid #e5e7eb; }
            .assignment-modal .summary-item:nth-child(1),
            .assignment-modal .summary-item:nth-child(2) { border-top: 0; }
            .assignment-modal .summary-item:nth-child(odd) { border-right: 1px solid #e5e7eb; }
        }
        @media (max-width: 575.98px) {
            .assignment-modal .summary-grid { grid-template-columns: minmax(0, 1fr); }
            .assignment-modal .summary-item { border-right: 0; }
        }
    </style>

    <div class="payroll-summary-card mb-3">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Gross Pay</div>
                <div class="summary-value">₱{{ number_format((float) $trainerGross, 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Deductions</div>
                <div class="summary-value" data-total-deductions>₱{{ number_format((float) $trainerTotalDeductions, 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Hours</div>
                <div class="summary-value">{{ $formatHours($assignment['total_hours'] ?? 0) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Net Pay</div>
                <div class="summary-value text-success" data-net>₱{{ number_format((float) $trainerNet, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="card payroll-card mb-3">
        <button
            class="btn payroll-card-toggle w-100 text-start d-flex align-items-center justify-content-between"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#{{ $modalId }}-deductions"
            aria-expanded="true"
            aria-controls="{{ $modalId }}-deductions"
        >
            <div class="d-flex align-items-center gap-2">
                <span class="payroll-icon"><i class="fa-solid fa-chart-column"></i></span>
                <span class="fw-semibold">Deductions Breakdown</span>
            </div>
            <i class="fa-solid fa-chevron-down"></i>
        </button>
        <div id="{{ $modalId }}-deductions" class="collapse show">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-2">Deductions</div>
                <ul class="list-unstyled small mb-0">
                    @if($trainerHasSss)
                        <li class="d-flex justify-content-between">
                            <span>SSS</span>
                            <span data-sss>₱{{ number_format($trainerSss, 2) }}</span>
                        </li>
                    @endif
                    @if($trainerHasPhilhealth)
                        <li class="d-flex justify-content-between">
                            <span>PhilHealth</span>
                            <span data-philhealth>₱{{ number_format($trainerPhilhealth, 2) }}</span>
                        </li>
                    @endif
                    @if($trainerHasPagibig)
                        <li class="d-flex justify-content-between">
                            <span>Pag-IBIG</span>
                            <span data-pagibig>₱{{ number_format($trainerPagibig, 2) }}</span>
                        </li>
                    @endif
                    <li class="d-flex justify-content-between">
                        <span>3kfitness app cut</span>
                        <span data-appcut>₱{{ number_format($trainerAppCut, 2) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="filter-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label text-muted text-uppercase small mb-1">Month</label>
                <input type="month" class="form-control form-control-sm" data-filter-month>
            </div>
            <div class="col-12 col-md-8 d-flex align-items-end gap-2"></div>
        </div>
    </div>

    <div class="assignment-list">
        <div class="table-responsive payroll-table">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Date</th>
                        <th class="text-end">Hours</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end">Gross Pay</th>
                        <th class="text-end">Net Pay</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $assignmentDetailsForDisplay = $assignmentDetails->filter(function ($detail) {
                            $paidDates = collect($detail['paid_dates'] ?? [])->filter();
                            $paidCount = (int) ($detail['past_paid_count'] ?? 0);
                            return $paidDates->isNotEmpty() || $paidCount > 0;
                        })->values();
                    @endphp
                    @forelse($assignmentDetailsForDisplay as $detail)
                        @php
                            $schedule = $detail['schedule'];
                            $processedMatch = $processedSeries->first(function ($series) use ($schedule) {
                                $sessions = $series['sessions'] ?? [];
                                if (empty($sessions)) {
                                    return false;
                                }
                                $matchId = isset($series['schedule_id'], $schedule->id) && (string) $series['schedule_id'] === (string) $schedule->id;
                                $matchCode = !empty($series['class_code']) && !empty($schedule->class_code) && $series['class_code'] === $schedule->class_code;
                                return $matchId || $matchCode;
                            });
                            $processedDates = collect($processedMatch['sessions'] ?? [])->map(function ($session) {
                                $date = $session['date'] ?? null;
                                if (!$date) {
                                    return null;
                                }
                                try {
                                    return \Carbon\Carbon::parse($date)->toDateString();
                                } catch (\Throwable $th) {
                                    return $date;
                                }
                            })->filter()->values();
                            $start = $detail['start'];
                            $end = $detail['end'];
                            $category = $detail['category'];
                            $paidDatesRaw = collect($detail['paid_dates'] ?? [])->filter();
                            $paidDateKeys = $paidDatesRaw->map(function ($date) {
                                try {
                                    return \Carbon\Carbon::parse($date)->toDateString();
                                } catch (\Throwable $th) {
                                    return $date;
                                }
                            })->filter()->values();
                            $hasPaid = $paidDatesRaw->isNotEmpty() || (int) ($detail['past_paid_count'] ?? 0) > 0;
                            $categoryLabel = $hasPaid ? 'Completed' : ($category === 'future' ? 'Upcoming' : 'Completed');
                            $badgeClass = $categoryLabel === 'Upcoming' ? 'bg-success text-white' : 'bg-secondary';
                            $rangeStart = $start ? $start->format('F j, Y g:i A') : 'N/A';
                            $rangeEnd = $end ? $end->format('F j, Y g:i A') : null;
                            $students = $detail['students'];
                            $hasAttendance = $detail['has_attendance'] ?? false;
                            $attendanceRecords = collect($detail['attendances'] ?? collect());
                            $attendanceList = $attendanceRecords->map(function ($record) {
                                $clockIn = $record['clockin_at'] ?? null;
                                $clockOut = $record['clockout_at'] ?? null;
                                $clockInLabel = $clockIn ? $clockIn->format('M d, Y g:i A') : '—';
                                $clockOutLabel = $clockOut ? $clockOut->format('M d, Y g:i A') : null;
                                return $clockOutLabel ? $clockInLabel . ' – ' . $clockOutLabel : $clockInLabel;
                            })->values();
                            $payableSalary = $detail['payroll_salary'] ?? 0;
                            $recurringLabel = $detail['recurring_label'] ?? '';
                            $occurrenceDatesRaw = collect($detail['occurrence_dates'] ?? collect())->filter();
                            $displayDatesRaw = $paidDatesRaw;
                            $attendanceItems = $displayDatesRaw->map(function ($date) use ($detail, $paidDateKeys) {
                                try {
                                    $dateKey = \Carbon\Carbon::parse($date)->toDateString();
                                } catch (\Throwable $th) {
                                    $dateKey = $date;
                                }

                                $isPaid = $paidDateKeys->contains($dateKey);
                                $isPast = collect($detail['past_dates'] ?? [])->contains($dateKey);
                                $isFuture = collect($detail['future_dates'] ?? [])->contains($dateKey);

                                if ($isPaid) {
                                    return ['label' => 'Completed', 'class' => 'bg-success-subtle text-success', 'filter' => 'present'];
                                }
                                if ($isPast) {
                                    return ['label' => 'Absent', 'class' => 'bg-danger-subtle text-danger', 'filter' => 'absent'];
                                }
                                if ($isFuture) {
                                    return ['label' => 'Upcoming', 'class' => 'bg-warning-subtle text-warning', 'filter' => null];
                                }

                                return ['label' => 'Absent', 'class' => 'bg-danger-subtle text-danger', 'filter' => 'absent'];
                            })->values();
                            $attendanceFilters = $attendanceItems
                                ->pluck('filter')
                                ->filter()
                                ->unique()
                                ->values();
                            $displayDates = $displayDatesRaw->map(function ($date) {
                                try {
                                    return \Carbon\Carbon::parse($date)->format('M d');
                                } catch (\Throwable $th) {
                                    return $date;
                                }
                            })->values();
                            $displayDateItems = $displayDates->isNotEmpty()
                                ? $displayDates
                                : collect([$start ? $start->format('M d') : '—']);
                            $rangeStartLabel = $displayDateItems->first();
                            $rangeEndLabel = $displayDateItems->last();
                            $dateRangeLabel = $rangeStartLabel ?? '—';
                            if ($rangeEndLabel && $rangeEndLabel !== $rangeStartLabel) {
                                $dateRangeLabel .= ' - ' . $rangeEndLabel;
                            }
                            $statusItems = $attendanceItems;
                            if ($statusItems->where('label', 'Completed')->count() > 1) {
                                $completedSeen = false;
                                $statusItems = $statusItems->filter(function ($item) use (&$completedSeen) {
                                    if (($item['label'] ?? '') !== 'Completed') {
                                        return true;
                                    }
                                    if ($completedSeen) {
                                        return false;
                                    }
                                    $completedSeen = true;
                                    return true;
                                })->values();
                            }
                            $startFilterDate = $displayDatesRaw->first() ?? ($detail['start_date'] ?? '');
                            $endFilterDate = $displayDatesRaw->last() ?? ($detail['end_date'] ?? '');
                            $occurrenceDaysSource = $displayDatesRaw->isNotEmpty() ? $displayDatesRaw : $occurrenceDatesRaw;
                            $occurrenceDays = $occurrenceDaysSource->map(function ($date) {
                                try {
                                    return \Carbon\Carbon::parse($date)->day;
                                } catch (\Throwable $th) {
                                    return null;
                                }
                            })->filter()->values();
                            $occurrenceTimeline = collect($detail['paid_dates'] ?? [])
                                ->map(function ($date) use ($detail, $processedDates, $processedLabel, $paidDateKeys) {
                                    try {
                                        $parsed = \Carbon\Carbon::parse($date);
                                    } catch (\Throwable $th) {
                                        return null;
                                    }
                                    $dateKey = $parsed->toDateString();
                                    $isProcessed = $processedDates->contains($dateKey);
                                    $isPaid = $paidDateKeys->contains($dateKey);
                                    $isPast = collect($detail['past_dates'] ?? [])->contains($dateKey);
                                    $isFuture = collect($detail['future_dates'] ?? [])->contains($dateKey);
                                    $status = $isFuture ? 'Upcoming' : ($isPaid ? 'Present' : ($isPast ? 'Absent' : '—'));
                                    $statusClass = 'bg-secondary';
                                    if ($isFuture) {
                                        $statusClass = 'bg-warning text-dark';
                                    } elseif ($isPaid) {
                                        $statusClass = 'bg-success';
                                    } elseif ($isPast) {
                                        $statusClass = 'bg-danger-subtle text-danger';
                                    }

                                    return [
                                        'label' => $parsed->format('M j, Y'),
                                        'status' => $status,
                                        'status_class' => $statusClass,
                                        'day' => $parsed->day,
                                        'processed' => $isProcessed,
                                        'processed_label' => $isProcessed ? $processedLabel : null,
                                    ];
                                })
                                ->filter()
                                ->values();
                            $detailPayload = [
                                'title' => $schedule->name ?? 'Unnamed Schedule',
                                'code' => $schedule->class_code ?? null,
                                'category' => $categoryLabel,
                                'time_range' => $detail['time_range'] ?? ($rangeStart !== 'N/A' ? trim($rangeStart . ($rangeEnd ? ' – ' . $rangeEnd : '')) : null),
                                'series' => [
                                    'start' => $rangeStart,
                                    'end' => $rangeEnd,
                                    'recurrence' => $recurringLabel,
                                    'hours_per_occurrence' => $detail['hours'] ?? 0,
                                ],
                                'counts' => [
                                    'upcoming' => $detail['future_occurrence_count'] ?? 0,
                                    'completed' => $detail['past_occurrence_count'] ?? 0,
                                    'paid' => $detail['past_paid_count'] ?? 0,
                                ],
                                'amounts' => [
                                    'upcoming' => $detail['future_potential_salary'] ?? $detail['display_salary'] ?? 0,
                                    'completed' => $payableSalary,
                                ],
                                'rate' => $schedule->trainer_rate_per_hour ?? null,
                                'students' => $students->values(),
                                'attendance' => $attendanceList,
                                'timeline' => $occurrenceTimeline->values(),
                            ];
                            $detailJson = json_encode($detailPayload, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT);
                            $rateValue = $schedule->trainer_rate_per_hour ?? null;
                            $hoursValue = $hasPaid ? ($detail['payroll_hours'] ?? 0) : ($detail['hours'] ?? 0);
                            $grossAmount = $hasPaid
                                ? (float) $payableSalary
                                : (float) ($detail['future_potential_salary'] ?? $detail['display_salary'] ?? 0);
                            $payableAmount = $hasPaid ? (float) $payableSalary : 0;
                            $netRatio = $trainerGross > 0 ? ($trainerNet / $trainerGross) : 0;
                            $rowNetPay = $grossAmount * $netRatio;
                            $rowCategory = $hasPaid ? 'past' : $category;
                        @endphp
                        <tr
                            class="assignment-row"
                            data-assignment-card
                            data-date-display="list"
                            data-category="{{ $rowCategory }}"
                            data-attendance="{{ $attendanceFilters->implode(',') }}"
                            data-start-date="{{ $startFilterDate }}"
                            data-end-date="{{ $endFilterDate }}"
                            data-future-salary="{{ (float) ($detail['future_potential_salary'] ?? $detail['display_salary'] ?? 0) }}"
                            data-past-salary="{{ (float) $payableSalary }}"
                            data-future-count="{{ (int) ($detail['future_occurrence_count'] ?? 0) }}"
                            data-past-count="{{ (int) ($detail['past_occurrence_count'] ?? 0) }}"
                            data-paid-count="{{ (int) ($detail['past_paid_count'] ?? 0) }}"
                            data-future-amount="{{ (float) ($detail['future_potential_salary'] ?? $detail['display_salary'] ?? 0) }}"
                            data-past-amount="{{ (float) ($detail['payroll_salary'] ?? 0) }}"
                            data-occurrence-days="{{ $occurrenceDays->implode(',') }}"
                            data-detail='{{ $detailJson }}'
                        >
                            <td>
                                <div class="fw-semibold">{{ $schedule->name ?? 'Unnamed Schedule' }}</div>
                                <div class="text-muted small">Code: {{ $schedule->class_code ?? '—' }}</div>
                                <button type="button" class="assignment-date-toggle mt-1" data-date-toggle aria-expanded="true">hide</button>
                            </td>
                            <td class="text-muted small">
                                <div class="assignment-date-list">
                                    @foreach($displayDateItems as $dateItem)
                                        <div>• {{ $dateItem }}</div>
                                    @endforeach
                                </div>
                                <div class="assignment-date-range">{{ $dateRangeLabel }}</div>
                            </td>
                            <td class="text-end">{{ $formatHours($hoursValue) }}</td>
                            <td class="text-end">
                                @if(!is_null($rateValue))
                                    ₱{{ number_format((float) $rateValue, 2) }}/hr
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">₱{{ number_format((float) $grossAmount, 2) }}</td>
                            <td
                                class="text-end fw-semibold"
                                data-row-net
                                data-row-gross="{{ number_format((float) $grossAmount, 2, '.', '') }}"
                            >
                                ₱{{ number_format((float) $rowNetPay, 2) }}
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    @foreach($statusItems as $statusItem)
                                        <span class="badge status-pill {{ $statusItem['class'] }}">{{ $statusItem['label'] }}</span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted small py-4">No assignments found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
            <div class="d-flex flex-wrap align-items-center gap-2 text-muted small">
                <span class="text-uppercase fw-semibold">Entries per page:</span>
                <select class="form-select form-select-sm w-auto entries-select" disabled>
                    <option selected>10</option>
                </select>
                <div class="btn-group btn-group-sm" role="group" aria-label="Pagination">
                    <button class="btn btn-outline-secondary" type="button" disabled>
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <span class="btn btn-outline-secondary disabled">1 of 1</span>
                    <button class="btn btn-outline-secondary" type="button" disabled>
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-danger btn-sm payslip-btn" data-payslip='{{ $trainerPayslipJson }}'>
                    <i class="fa-solid fa-print"></i>
                    Print
                </button>
                <button type="button" class="btn btn-success btn-sm" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
            @else
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body text-center py-5">
                            <h5 class="fw-semibold mb-2">No trainer payroll data found</h5>
                            <p class="text-muted mb-3">Try selecting a different month or adjusting your search filters.</p>
                            <a href="{{ route('admin.payrolls.index') }}" class="btn btn-danger rounded-pill px-4">Go back to payroll list</a>
                        </div>
                    </div>
                </div>
            @endif
            </section>
        </div>
    </div>

@push('modals')
    {{-- Deduction rules modal --}}
    <div class="modal fade" id="deductionModal" tabindex="-1" aria-labelledby="deductionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-sm">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="deductionModalLabel">Adjust deduction rules</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <style>
                        .deduction-card {
                            border: 1px solid #e5e7eb;
                            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
                        }
                        .pill-badge {
                            display: inline-flex;
                            align-items: center;
                            gap: 6px;
                            border-radius: 999px;
                            padding: 6px 12px;
                            background: #f3f4f6;
                            color: #111827;
                            font-weight: 600;
                            font-size: 13px;
                            white-space: normal;
                            line-height: 1.3;
                            max-width: 100%;
                            word-break: break-word;
                        }
                        .pill-badge.block {
                            display: block;
                            width: 100%;
                            text-align: left;
                        }
                        .pill-badge.danger { background: #fef2f2; color: #b91c1c; }
                        .pill-badge.success { background: #ecfdf3; color: #166534; }
                        .pill-badge.info { background: #eef2ff; color: #312e81; }
                    </style>
                    <p class="text-muted small mb-3">Set the current government rates. Changes update the on-screen calculations and payslip printout.</p>
                    <div class="rounded-4 deduction-card p-3 mb-3 bg-white">
                        <div class="d-flex align-items-start gap-3 flex-wrap">
                            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="text-muted small text-uppercase fw-semibold mb-1">Payroll month</div>
                                <div class="fw-bold">{{ $monthLabel }}</div>
                                <div class="text-muted small mb-0">Process payroll is unlocked only on the processing days listed below.</div>
                            </div>
                            <span class="pill-badge info block flex-shrink-1" style="min-width: 220px;" data-activation-status>Processing enabled</span>
                        </div>
                    </div>
                    <form
                        id="deduction-form"
                        class="row g-3"
                        method="POST"
                        action="{{ route('admin.payrolls.deductions.update') }}"
                    >
                        @csrf
                        <div class="col-12">
                            <div class="border rounded-4 p-3 bg-light-subtle">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <label class="form-label text-muted text-uppercase small mb-1 mb-0">Processing day ranges</label>
                                        <div class="text-muted small mb-0">Map day ranges to their processing day (e.g., 1-15 → 20, 21-31 → 5).</div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-range-row">
                                        <i class="fa-solid fa-plus"></i>
                                        Add range
                                    </button>
                                </div>
                                @php
                                    $processingRanges = $deductionSettings['processing_day_ranges'] ?? [];
                                @endphp
                                <div class="d-flex flex-column gap-2" id="range-list">
                                    <div data-range-rows class="d-flex flex-column gap-2">
                                        @forelse($processingRanges as $range)
                                            <div class="row g-2 align-items-center range-row">
                                                <div class="col-4">
                                                    <input type="number" min="1" max="31" name="processing_day_ranges[from][]" class="form-control" value="{{ $range['from'] ?? '' }}" placeholder="From day">
                                                </div>
                                                <div class="col-4">
                                                    <input type="number" min="1" max="31" name="processing_day_ranges[to][]" class="form-control" value="{{ $range['to'] ?? '' }}" placeholder="To day">
                                                </div>
                                                <div class="col-3">
                                                    <input type="number" min="1" max="31" name="processing_day_ranges[process][]" class="form-control" value="{{ $range['process'] ?? '' }}" placeholder="Process day">
                                                </div>
                                                <div class="col-1 d-flex justify-content-end">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" data-remove-range>
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="row g-2 align-items-center range-row">
                                                <div class="col-4">
                                                    <input type="number" min="1" max="31" name="processing_day_ranges[from][]" class="form-control" value="1" placeholder="From day">
                                                </div>
                                                <div class="col-4">
                                                    <input type="number" min="1" max="31" name="processing_day_ranges[to][]" class="form-control" value="15" placeholder="To day">
                                                </div>
                                                <div class="col-3">
                                                    <input type="number" min="1" max="31" name="processing_day_ranges[process][]" class="form-control" value="20" placeholder="Process day">
                                                </div>
                                                <div class="col-1 d-flex justify-content-end">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" data-remove-range>
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row g-2 align-items-center range-row">
                                                <div class="col-4">
                                                    <input type="number" min="1" max="31" name="processing_day_ranges[from][]" class="form-control" value="21" placeholder="From day">
                                                </div>
                                                <div class="col-4">
                                                    <input type="number" min="1" max="31" name="processing_day_ranges[to][]" class="form-control" value="31" placeholder="To day">
                                                </div>
                                                <div class="col-3">
                                                    <input type="number" min="1" max="31" name="processing_day_ranges[process][]" class="form-control" value="5" placeholder="Process day">
                                                </div>
                                                <div class="col-1 d-flex justify-content-end">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" data-remove-range>
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforelse
                                    </div>
                                    <p class="text-muted small mb-0" data-range-empty>Add at least one range to control processing days per cutoff.</p>
                                    <div class="d-flex flex-wrap gap-2 mt-2" id="range-preview"></div>
                                </div>
                                <template id="range-row-template">
                                    <div class="row g-2 align-items-center range-row">
                                        <div class="col-4">
                                            <input type="number" min="1" max="31" name="processing_day_ranges[from][]" class="form-control" placeholder="From day">
                                        </div>
                                        <div class="col-4">
                                            <input type="number" min="1" max="31" name="processing_day_ranges[to][]" class="form-control" placeholder="To day">
                                        </div>
                                        <div class="col-3">
                                            <input type="number" min="1" max="31" name="processing_day_ranges[process][]" class="form-control" placeholder="Process day">
                                        </div>
                                        <div class="col-1 d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-danger btn-sm" data-remove-range>
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-muted text-uppercase small mb-1">SSS (%)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                id="rate-sss"
                                name="sss_rate"
                                value="{{ $deductionSettings['sss_rate'] ?? 0 }}"
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-muted text-uppercase small mb-1">PhilHealth (%)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                id="rate-philhealth"
                                name="philhealth_rate"
                                value="{{ $deductionSettings['philhealth_rate'] ?? 0 }}"
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-muted text-uppercase small mb-1">Pag-IBIG (%)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                id="rate-pagibig"
                                name="pagibig_rate"
                                value="{{ $deductionSettings['pagibig_rate'] ?? 0 }}"
                            >
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-muted text-uppercase small mb-1">3kfitness app cut (%)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                id="rate-appcut"
                                name="app_cut_rate"
                                value="{{ $deductionSettings['app_cut_rate'] ?? 0 }}"
                            >
                            <p class="text-muted small mt-1 mb-0">Staff payroll uses a fixed 0% app cut; this rate applies to trainer calculations only.</p>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-muted text-uppercase small mb-1">Pag-IBIG max base (₱)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                id="cap-pagibig"
                                name="pagibig_cap"
                                value="{{ $deductionSettings['pagibig_cap'] ?? 0 }}"
                            >
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="apply-deductions">Apply on page</button>
                    <button type="submit" class="btn btn-primary" form="deduction-form">Save settings</button>
                </div>
            </div>
        </div>
    </div>

@endpush

    {{-- Process confirmation modal --}}
    <div class="modal fade" id="processConfirmModal" tabindex="-1" aria-labelledby="processConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-sm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-semibold mb-0" id="processConfirmModalLabel">Confirm payroll processing</h5>
                        <p class="text-muted small mb-0">Double-check the source data before saving this payroll run.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning d-none" data-confirm-pending-alert>
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        Some entries are still pending clock-out. Finalize attendance before proceeding.
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small text-uppercase fw-semibold mb-1">Run summary</div>
                                <ul class="list-unstyled small mb-0">
                                    <li class="d-flex justify-content-between mb-1">
                                        <span>Person</span>
                                        <span class="fw-semibold" data-confirm-name>—</span>
                                    </li>
                                    <li class="d-flex justify-content-between mb-1">
                                        <span>Role</span>
                                        <span data-confirm-role>—</span>
                                    </li>
                                    <li class="d-flex justify-content-between mb-1">
                                        <span>Period</span>
                                        <span data-confirm-month>—</span>
                                    </li>
                                    <li class="d-flex justify-content-between mb-1">
                                        <span>Basis</span>
                                        <span data-confirm-basis>—</span>
                                    </li>
                                    <li class="d-flex justify-content-between mb-1">
                                        <span>Pending entries</span>
                                        <span data-confirm-pending>0</span>
                                    </li>
                                </ul>
                                <p class="text-muted small mb-0 mt-2">
                                    This will create a saved payroll run using the source data above. No deductions are changed here—use "Adjust deductions" if rates need updates.
                                </p>
                            </div>
                        </div>
                        <div class="col-12 col-md-5">
                            <div class="border rounded-4 p-3 h-100 bg-white">
                                <div class="text-muted small text-uppercase fw-semibold mb-2">Payout snapshot</div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">Hours</span>
                                    <span class="fw-semibold" data-confirm-hours>0 hrs</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">Gross</span>
                                    <span class="fw-semibold" data-confirm-gross>₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Net (after deductions)</span>
                                    <span class="fw-bold text-success" data-confirm-net>₱0.00</span>
                                </div>
                                <div class="bg-light-subtle rounded-3 p-2 mt-2">
                                    <div class="text-muted small mb-1">Reminder</div>
                                    <p class="text-muted small mb-0">Staff: based on clock-ins/outs. Trainers: based on paid class sessions with attendance.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="process-confirm-submit">Process now</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Process preview modal --}}
    <div class="modal fade" id="processPreviewModal" tabindex="-1" aria-labelledby="processPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-sm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-semibold mb-0" id="processPreviewModalLabel">Assignments to be processed</h5>
                        <p class="text-muted small mb-0" data-process-subtitle>Only class sessions with attendance inside the processing window are queued.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <style>
                        .process-list { max-height: 60vh; overflow-y: auto; }
                        .process-card { border: 1px solid #e5e7eb; border-radius: 16px; padding: 14px 16px; background: #f8fafc; }
                        .process-card + .process-card { margin-top: 10px; }
                        .process-timeline { border-left: 2px solid #e5e7eb; padding-left: 10px; margin-left: 6px; }
                        .process-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; }
                    </style>
                    <div class="card border-0 bg-light rounded-4 mb-3">
                        <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small text-uppercase fw-semibold">Processing day</div>
                                <div class="text-muted small mb-0">Choose a processing day range to preview what will be processed.</div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <span class="pill-badge info" id="process-range-summary">Select a processing day range</span>
                                <span class="pill-badge" id="process-range-total">Gross pay ₱0.00</span>
                                <span class="pill-badge success" id="process-range-deductions">Deductions ₱0.00</span>
                                <span class="pill-badge" id="process-range-count">0 assignments</span>
                            </div>
                        </div>
                    </div>

                    @php $ranges = $deductionSettings['processing_day_ranges'] ?? []; @endphp
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6 col-lg-4">
                            <label class="form-label text-muted text-uppercase small mb-1">Processing day range</label>
                            <select class="form-select form-select-sm" id="process-day-filter" {{ empty($ranges) ? 'disabled' : '' }}>
                                @foreach($ranges as $idx => $range)
                                    <option value="{{ $idx }}">{{ $range['from'] ?? '?' }}-{{ $range['to'] ?? '?' }} → process on day {{ $range['process'] ?? '?' }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" data-process-helper>Pick a range to see included classes.</div>
                        </div>
                    </div>

                    <div class="assignment-list process-list" id="process-range-list">
                        <div class="text-center text-muted">No assignments found.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="process-range-confirm">Continue processing</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const payrollMonthLabel = @json($monthLabel);
        const payrollMonthValue = @json($month);
        const toggleButtons = document.querySelectorAll('[data-payroll-toggle]');
        const staffSection = document.getElementById('staff-payroll-section');
        const trainerSection = document.getElementById('trainer-payroll-section');
        const deductionModalEl = document.getElementById('deductionModal');
        if (deductionModalEl && deductionModalEl.parentElement !== document.body) {
            document.body.appendChild(deductionModalEl);
        }
        const deductionTrigger = document.querySelector('[data-bs-target="#deductionModal"]');
        if (deductionTrigger && deductionModalEl) {
            deductionTrigger.addEventListener('click', () => {
                if (deductionModalEl.classList.contains('show') || typeof bootstrap === 'undefined') {
                    return;
                }
                bootstrap.Modal.getOrCreateInstance(deductionModalEl).show();
            });
        }
        const formatHoursWithMinutes = function (value) {
            const hours = Number(value);
            if (!Number.isFinite(hours)) return '0 hrs';
            const safeHours = Math.max(0, hours);
            let wholeHours = Math.floor(safeHours);
            let minutes = Math.round((safeHours - wholeHours) * 60);
            if (minutes === 60) {
                wholeHours += 1;
                minutes = 0;
            }
            const parts = [];
            if (wholeHours > 0 || minutes === 0) {
                parts.push(`${wholeHours.toLocaleString()} ${wholeHours === 1 ? 'hr' : 'hrs'}`);
            }
            if (minutes > 0) {
                parts.push(`${minutes.toLocaleString()} ${minutes === 1 ? 'min' : 'mins'}`);
            }
            return parts.join(' ');
        };

        function setSection(mode) {
            toggleButtons.forEach((btn) => {
                btn.classList.toggle('active', btn.dataset.payrollToggle === mode);
            });

            const showStaff = mode === 'staff' || mode === 'both';
            const showTrainer = mode === 'trainer' || mode === 'both';

            if (staffSection) {
                staffSection.classList.toggle('d-none', !showStaff);
            }
            if (trainerSection) {
                trainerSection.classList.toggle('d-none', !showTrainer);
            }
        }

        toggleButtons.forEach((btn) => {
            btn.addEventListener('click', () => setSection(btn.dataset.payrollToggle || 'staff'));
        });
        setSection('staff');

        const serverProcessingDays = @json($deductionSettings['processing_days'] ?? []);
        const serverProcessingRanges = @json($deductionSettings['processing_day_ranges'] ?? []);
        const today = new Date();
        const todayDay = today.getDate();
        const todayLabel = today.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const activationStatus = document.querySelector('[data-activation-status]');
        const rangeRows = document.querySelector('[data-range-rows]');
        const rangeTemplate = document.getElementById('range-row-template');
        const addRangeBtn = document.getElementById('add-range-row');
        const rangePreview = document.getElementById('range-preview');
        const rangeEmpty = document.querySelector('[data-range-empty]');
        let activationDays = []; // processing days UI removed; rely on ranges
        let processingRanges = Array.isArray(serverProcessingRanges) ? serverProcessingRanges.slice() : [];

        function normalizeActivationDays() {
            activationDays = activationDays
                .map((value) => {
                    if (typeof value === 'string' && value.toLowerCase() === 'eom') return 'eom';
                    const parsed = parseInt(value, 10);
                    return Number.isInteger(parsed) ? parsed : '';
                })
                .filter((value) => value === 'eom' || (Number.isInteger(value) && value >= 1 && value <= 31))
                .filter((value, index, arr) => arr.indexOf(value) === index);
        }

        function getNormalizedRanges() {
            return processingRanges
                .map((range) => {
                    const from = parseInt(range.from, 10);
                    const to = parseInt(range.to ?? 31, 10);
                    const process = parseInt(range.process, 10);
                    if (!Number.isInteger(from) || !Number.isInteger(process)) return null;
                    if (from < 1 || from > 31 || process < 1 || process > 31) return null;
                    const toSafe = Number.isInteger(to) && to >= 1 && to <= 31 ? to : 31;
                    const fromSafe = Math.min(from, toSafe);
                    const toFinal = Math.max(from, toSafe);
                    return { from: fromSafe, to: toFinal, process };
                })
                .filter(Boolean);
        }

        function defaultRanges() {
            return [
                { from: 1, to: 15, process: 20 },
                { from: 21, to: 31, process: 5 },
            ];
        }

        function renderRangeRows() {
            if (!rangeRows || !rangeTemplate) return;
            rangeRows.innerHTML = '';
            const rows = processingRanges.length ? processingRanges : defaultRanges();
            rows.forEach((range, index) => {
                const node = rangeTemplate.content.firstElementChild.cloneNode(true);
                const inputs = node.querySelectorAll('input');
                inputs.forEach((input) => {
                    if (input.name.includes('[from]')) input.value = range.from ?? '';
                    if (input.name.includes('[to]')) input.value = range.to ?? '';
                    if (input.name.includes('[process]')) input.value = range.process ?? '';
                    input.addEventListener('change', () => {
                        const fromVal = node.querySelector('input[name*="[from]"]')?.value;
                        const toVal = node.querySelector('input[name*="[to]"]')?.value;
                        const processVal = node.querySelector('input[name*="[process]"]')?.value;
                        processingRanges[index] = { from: fromVal, to: toVal, process: processVal };
                        renderRangePreview();
                        updateProcessButtons();
                    });
                });
                node.querySelector('[data-remove-range]')?.addEventListener('click', () => {
                    processingRanges.splice(index, 1);
                    renderRangeRows();
                    renderRangePreview();
                    updateProcessButtons();
                });
                rangeRows.appendChild(node);
            });

            if (rangeEmpty) {
                rangeEmpty.classList.toggle('d-none', processingRanges.length > 0);
            }
        }

        function renderRangePreview() {
            if (!rangePreview) return;
            rangePreview.innerHTML = '';
            const ranges = getNormalizedRanges();
            if (!ranges.length) {
                const span = document.createElement('span');
                span.className = 'text-muted small';
                span.textContent = 'No ranges set.';
                rangePreview.appendChild(span);
                return;
            }
            ranges.forEach((range) => {
                const badge = document.createElement('span');
                badge.className = 'pill-badge';
                badge.textContent = `${range.from}-${range.to} → process on day ${range.process}`;
                rangePreview.appendChild(badge);
            });
        }

        function getAllowedDays() {
            const rangeDays = getNormalizedRanges()
                .map((r) => r.process)
                .filter((v, i, arr) => arr.indexOf(v) === i);
            const explicitDays = activationDays;
            return [...new Set([...explicitDays, ...rangeDays])];
        }

        function parsePayrollMonth(value) {
            if (!value || typeof value !== 'string') return null;
            const [year, month] = value.split('-').map((v) => parseInt(v, 10));
            if (Number.isNaN(year) || Number.isNaN(month)) return null;
            return { year, monthIndex: month - 1 };
        }

        function computeNextProcessingDate() {
            const allowedDays = getAllowedDays();
            if (!allowedDays.length) return null;
            const today = new Date();
            const parsed = parsePayrollMonth(payrollMonthValue);
            const baseYear = parsed ? parsed.year : today.getFullYear();
            const baseMonth = parsed ? parsed.monthIndex : today.getMonth();
            const candidates = [];

            [0, 1].forEach((offset) => {
                const base = new Date(baseYear, baseMonth + offset, 1);
                allowedDays.forEach((day) => {
                    let dayNum = null;
                    if (day === 'eom') {
                        dayNum = new Date(base.getFullYear(), base.getMonth() + 1, 0).getDate();
                    } else {
                        const parsedDay = parseInt(day, 10);
                        if (Number.isNaN(parsedDay)) return;
                        dayNum = parsedDay;
                    }
                    if (dayNum < 1 || dayNum > 31) return;
                    candidates.push(new Date(base.getFullYear(), base.getMonth(), dayNum));
                });
            });

            const future = candidates.filter((date) => date > today).sort((a, b) => a - b);
            if (future.length) return future[0];
            const ordered = candidates.sort((a, b) => a - b);
            return ordered[0] ?? null;
        }

        function setCooldownDisplays() {
            const nextDate = computeNextProcessingDate();
            const label = nextDate
                ? `Next payroll window: ${nextDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`
                : 'Next payroll window is not configured yet.';
            document.querySelectorAll('[data-cooldown-display]').forEach((el) => {
                el.textContent = label;
            });
        }

        function isDayAllowed() {
            const allowedDays = getAllowedDays();
            if (!allowedDays.length) return false;
            const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
            return allowedDays.some((value) => {
                if (value === 'eom') {
                    return todayDay === lastDayOfMonth;
                }
                return value === todayDay;
            });
        }

        function updateActivationStatusBadge(allowed, dayAllowed) {
            if (!activationStatus) return;

            let text = '';
            if (allowed) {
                text = `Processing enabled today (${todayLabel}).`;
            } else if (!getAllowedDays().length) {
                text = 'Locked: add at least one allowed processing day.';
            } else if (!dayAllowed) {
                text = `Locked: today (${todayLabel}) is not in the allowed days list (including end-of-month if added).`;
            } else {
                text = 'Processing locked for this period.';
            }

            activationStatus.textContent = text;
            activationStatus.className = allowed
                ? 'badge bg-success-subtle text-success rounded-pill px-3 py-2'
                : 'badge bg-warning-subtle text-warning rounded-pill px-3 py-2';
        }

        function updateProcessButtons() {
            const dayAllowed = isDayAllowed();
            const allowed = dayAllowed;
            let lockMessage = 'Processing is locked for this period.';

            const allowedDaysList = getAllowedDays();

            if (!allowedDaysList.length) {
                lockMessage = 'Processing allowed only on configured days. Add at least one processing day in Adjust deductions.';
            } else if (!dayAllowed) {
                const list = allowedDaysList.length
                    ? allowedDaysList.map((value) => value === 'eom' ? 'End of month' : value).join(', ')
                    : 'the allowed days list';
                lockMessage = `Processing allowed only on days ${list}. Today (${todayLabel}) is not allowed.`;
            }

            document.querySelectorAll('.process-payroll-btn').forEach((btn) => {
                const baseDisabled = btn.dataset.baseDisabled === '1';
                const baseTitle = btn.dataset.baseTitle || btn.getAttribute('title') || '';

                if (!allowed) {
                    btn.disabled = true;
                    btn.title = lockMessage;
                } else {
                    btn.disabled = baseDisabled;
                    btn.title = baseTitle;
                }
            });

            updateActivationStatusBadge(allowed, dayAllowed);
            setCooldownDisplays();
        }

        normalizeActivationDays();
        if (!processingRanges.length) {
            processingRanges = defaultRanges();
        }

        addRangeBtn?.addEventListener('click', () => {
            processingRanges.push({ from: '', to: '', process: '' });
            renderRangeRows();
            renderRangePreview();
            updateProcessButtons();
        });

        updateProcessButtons();
        renderRangeRows();
        renderRangePreview();

        const payslipButtons = document.querySelectorAll('.payslip-btn');
        payslipButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                let data = {};
                try {
                    data = JSON.parse(btn.dataset.payslip || '{}');
                } catch (e) {
                    console.error('Invalid payslip data', e);
                    return;
                }

                const entries = Array.isArray(data.entries) ? data.entries : [];
                const assignments = Array.isArray(data.assignments) ? data.assignments : [];
                const membershipPayments = Array.isArray(data.membership_payments?.items) ? data.membership_payments.items : [];
                const isTrainer = data.type === 'trainer';
                const employmentType = (data.employment_type && String(data.employment_type).trim() !== '')
                    ? data.employment_type
                    : (isTrainer ? 'Contractor / Freelancer' : '');
                const normalizeAmount = (value) => {
                    const num = Number(value);
                    return Number.isFinite(num) ? num : 0;
                };
                const isZeroAmount = (value) => Math.abs(normalizeAmount(value)) < 0.005;
                const formatMoney = (value) => `₱${normalizeAmount(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                const formatNumber = (value) => {
                    const num = Number(value);
                    if (Number.isFinite(num)) {
                        return num.toLocaleString();
                    }
                    return value ?? '—';
                };
                const renderDateList = (value) => {
                    const dates = Array.isArray(value) ? value : (value ? [value] : []);
                    if (!dates.length) return '—';
                    return `<div class="date-bullets">${dates.map((date) => `<div>• ${date}</div>`).join('')}</div>`;
                };
                const totalHours = formatHoursWithMinutes(data.hours);
                const generatedBy = data.generated_by || '—';
                const generatedDate = data.generated_at || new Date().toLocaleString();
                const style = `
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; color: #111827; }
                        .payslip { max-width: 800px; margin: 0 auto; border: 1px solid #e5e7eb; padding: 24px; border-radius: 12px; }
                        .header { text-align: center; margin-bottom: 24px; }
                        .header h1 { margin: 0 0 8px; }
                        .muted { color: #6b7280; font-size: 13px; }
                        .section { margin-bottom: 20px; }
                        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
                        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
                        th { background: #f3f4f6; }
                        .totals { background: #fef2f2; }
                        .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; }
                        .badge-success { background: #dcfce7; color: #166534; }
                        .badge-warning { background: #fef9c3; color: #854d0e; }
                        .date-bullets { display: grid; gap: 2px; }
                        .date-bullets div { line-height: 1.2; }
                    </style>
                `;

                const rows = entries.map((entry) => {
                    const status = entry.status === 'complete'
                        ? '<span class="badge badge-success">Complete</span>'
                        : '<span class="badge badge-warning">Pending</span>';

                    return `
                        <tr>
                            <td>#${formatNumber(entry.id)}</td>
                            <td>${entry.clockin ?? '—'}</td>
                            <td>${entry.clockout ?? '—'}</td>
                            <td>${formatHoursWithMinutes(entry.hours)}</td>
                            <td>${formatMoney(entry.amount)}</td>
                            <td>${status}</td>
                        </tr>
                    `;
                }).join('');
                const assignmentRows = assignments.map((assignment) => `
                        <tr>
                            <td>
                                ${assignment.title || '—'}
                                ${assignment.code ? `<div class="muted">${assignment.code}</div>` : ''}
                                ${
                                    (() => {
                                        const rateValue = Number(assignment.rate);
                                        return Number.isFinite(rateValue) && rateValue > 0
                                            ? `<div class="muted">Rate: ${formatMoney(rateValue)}/hr</div>`
                                            : '';
                                    })()
                                }
                                ${assignment.recurrence ? `<div class="muted">Recurring: ${assignment.recurrence}</div>` : ''}
                            </td>
                            <td>${renderDateList(assignment.dates ?? assignment.date)}</td>
                            <td>${assignment.time || '—'}</td>
                            <td>
                                ${
                                    (() => {
                                        const list = Array.isArray(assignment.attendance) ? assignment.attendance : [];
                                        const uniqueList = list.filter((item, index) => list.indexOf(item) === index);
                                        return uniqueList.length
                                            ? uniqueList.map((slot) => `<div>${slot}</div>`).join('')
                                            : '<span class="muted">No attendance</span>';
                                    })()
                                }
                            </td>
                            <td>${formatHoursWithMinutes(assignment.hours)}</td>
                            <td>${formatMoney(assignment.salary)}</td>
                        </tr>
                    `).join('');
                const infoFields = [
                    `<div><strong>${isTrainer ? 'Trainer' : 'Employee'}:</strong> ${data.name || '—'}</div>`,
                    `<div><strong>Email:</strong> ${data.email || '—'}</div>`,
                    `<div><strong>Period:</strong> ${data.month || '—'}</div>`,
                    `<div><strong>Employment Type:</strong> ${employmentType}</div>`,
                    `<div><strong>Total hours:</strong> ${totalHours}</div>`,
                    `<div><strong>Generated By:</strong> ${generatedBy}</div>`,
                    `<div><strong>Generated Date:</strong> ${generatedDate}</div>`,
                ];

                const detailSection = isTrainer
                    ? `
                        <div class="section">
                            <strong>Assignments with attendance</strong>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Class/Schedule</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Attendance</th>
                                        <th>Hours</th>
                                        <th>Gross pay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${assignmentRows || '<tr><td colspan="6" style="text-align:center;">No assignments with attendance for this period.</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    `
                    : `
                        <div class="section">
                            <strong>Entries</strong>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Entry</th>
                                        <th>Clock in</th>
                                        <th>Clock out</th>
                                        <th>Hours</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rows || '<tr><td colspan="6" style="text-align:center;">No entries</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    `;

                const html = `
                    <!doctype html>
                    <html>
                        <head>
                            <title>Payslip - ${data.name || ''}</title>
                            ${style}
                        </head>
                        <body>
                            <div class="payslip">
                                <div class="header">
                                    <h1>Payroll Payslip</h1>
                                    <div class="muted">3kfitness Gym • ${data.month || ''}</div>
                                </div>
                                <div class="section grid">
                                    ${infoFields.join('')}
                                </div>
                                ${detailSection}
                                <div class="section">
                                    <strong>Total Summary</strong>
                                    <table class="totals">
                                        <tbody>
                                            ${
                                                [
                                                    { label: 'Gross pay', value: data.gross },
                                                    { label: 'SSS', value: data.deductions?.sss, isDeduction: true },
                                                    { label: 'PhilHealth', value: data.deductions?.philhealth, isDeduction: true },
                                                    { label: 'Pag-IBIG', value: data.deductions?.pagibig, isDeduction: true },
                                                    { label: '3kfitness app cut', value: data.deductions?.app_cut, isDeduction: true },
                                                    { label: 'Net pay', value: data.net, isTotal: true },
                                                ]
                                                    .filter((row) => {
                                                        if (row.isTotal || row.label === 'Gross pay') return true;
                                                        return !isZeroAmount(row.value);
                                                    })
                                                    .map((row) => {
                                                        const cell = `${formatMoney(row.value)}`;
                                                        if (row.isTotal) {
                                                            return `<tr><th>${row.label}</th><th>${cell}</th></tr>`;
                                                        }
                                                        return `<tr><td>${row.label}</td><td>${cell}</td></tr>`;
                                                    })
                                                    .join('')
                                            }
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <script>window.print();<\/script>
                        </body>
                    </html>
                `;

                const printWindow = window.open('', '_blank', 'width=900,height=1200');
                if (!printWindow) return;
                printWindow.document.open();
                printWindow.document.write(html);
                printWindow.document.close();
            });
        });

        // Deduction recalculation
        const applyBtn = document.getElementById('apply-deductions');
        const form = document.getElementById('deduction-form');
        const sssInput = document.getElementById('rate-sss');
        const philhealthInput = document.getElementById('rate-philhealth');
        const pagibigInput = document.getElementById('rate-pagibig');
        const pagibigCapInput = document.getElementById('cap-pagibig');
        const appCutInput = document.getElementById('rate-appcut');

        function formatPeso(value) {
            return `₱${Number(value || 0).toFixed(2)}`;
        }

        function applyDeductions() {
            const sssRate = Number(sssInput.value || 0) / 100;
            const philRate = Number(philhealthInput.value || 0) / 100;
            const pagibigRate = Number(pagibigInput.value || 0) / 100;
            const pagibigCap = Number(pagibigCapInput.value || 0);
            const appCutRateTrainer = Number(appCutInput?.value || 0) / 100;

            document.querySelectorAll('[data-payroll-card], [data-trainer-card]').forEach((card) => {
                const gross = Number(card.dataset.gross || 0);
                const isTrainer = card.hasAttribute('data-trainer-card');
                const hasSss = card.dataset.hasSss === '1';
                const hasPhilhealth = card.dataset.hasPhilhealth === '1';
                const hasPagibig = card.dataset.hasPagibig === '1';

                const sss = hasSss ? +(gross * sssRate).toFixed(2) : 0;
                const philhealth = hasPhilhealth ? +(gross * philRate).toFixed(2) : 0;
                const pagibigBase = pagibigCap > 0 ? Math.min(gross, pagibigCap) : gross;
                const pagibig = hasPagibig ? +(pagibigBase * pagibigRate).toFixed(2) : 0;
                const appCut = isTrainer ? +(gross * appCutRateTrainer).toFixed(2) : 0;
                const totalDeductions = +(sss + philhealth + pagibig + appCut).toFixed(2);
                const net = Math.max(gross - totalDeductions, 0);

                const updateContainer = (container) => {
                    if (!container) return;
                    container.querySelectorAll('[data-sss]').forEach((el) => el.textContent = formatPeso(sss));
                    container.querySelectorAll('[data-philhealth]').forEach((el) => el.textContent = formatPeso(philhealth));
                    container.querySelectorAll('[data-pagibig]').forEach((el) => el.textContent = formatPeso(pagibig));
                    container.querySelectorAll('[data-appcut]').forEach((el) => el.textContent = formatPeso(appCut));
                    container.querySelectorAll('[data-total-deductions]').forEach((el) => el.textContent = formatPeso(totalDeductions));
                    container.querySelectorAll('[data-net]').forEach((el) => el.textContent = formatPeso(net));
                    container.querySelectorAll('[data-row-net]').forEach((el) => {
                        const rowGross = Number(el.dataset.rowGross || 0);
                        const rowNet = gross > 0 ? rowGross * (net / gross) : 0;
                        el.textContent = formatPeso(rowNet);
                    });

                    const payslipBtn = container.querySelector('.payslip-btn');
                    if (payslipBtn) {
                        let data = {};
                        try {
                            data = JSON.parse(payslipBtn.dataset.payslip || '{}');
                        } catch (e) {
                            data = {};
                        }
                        data.deductions = { sss, philhealth, pagibig, app_cut: appCut };
                        data.net = net;
                        payslipBtn.dataset.payslip = JSON.stringify(data);
                    }
                };

                updateContainer(card);
                const modalId = card.dataset.modalId;
                if (modalId) {
                    updateContainer(document.getElementById(modalId));
                }
            });
        }

        applyBtn?.addEventListener('click', () => {
            applyDeductions();
            const modalEl = document.getElementById('deductionModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal?.hide();
        });

        // Generic confirmation modal (staff + trainer without preview)
        const confirmModalEl = document.getElementById('processConfirmModal');
        const confirmSubmitBtn = document.getElementById('process-confirm-submit');
        const confirmFields = {
            name: document.querySelector('[data-confirm-name]'),
            role: document.querySelector('[data-confirm-role]'),
            month: document.querySelector('[data-confirm-month]'),
            basis: document.querySelector('[data-confirm-basis]'),
            hours: document.querySelector('[data-confirm-hours]'),
            gross: document.querySelector('[data-confirm-gross]'),
            net: document.querySelector('[data-confirm-net]'),
            pending: document.querySelector('[data-confirm-pending]'),
            pendingAlert: document.querySelector('[data-confirm-pending-alert]'),
        };
        let pendingConfirmForm = null;

        if (confirmModalEl && confirmModalEl.parentElement !== document.body) {
            document.body.appendChild(confirmModalEl);
        }

        function openConfirmModal(btn, form) {
            if (!confirmModalEl) {
                form?.submit();
                return;
            }

            const role = (btn.dataset.role || '').toLowerCase() === 'trainer' ? 'trainer' : 'staff';
            const basis = btn.dataset.basis
                || (role === 'trainer' ? 'Classes + attendance per session' : 'Attendance clock-ins/outs');
            const name = btn.dataset.name || 'Payroll run';
            const month = btn.dataset.month || payrollMonthLabel || '';
            const hours = Number(btn.dataset.hours || 0);
            const gross = Number(btn.dataset.gross || 0);
            const net = Number(btn.dataset.net || 0);
            const pending = Number(btn.dataset.pending || 0);

            pendingConfirmForm = form;

            if (confirmFields.name) confirmFields.name.textContent = name;
            if (confirmFields.role) {
                confirmFields.role.textContent = role === 'trainer'
                    ? 'Trainer (classes + attendance)'
                    : 'Staff (attendance-based)';
            }
            if (confirmFields.month) confirmFields.month.textContent = month;
            if (confirmFields.basis) confirmFields.basis.textContent = basis;
            if (confirmFields.hours) confirmFields.hours.textContent = formatHoursWithMinutes(hours);
            if (confirmFields.gross) confirmFields.gross.textContent = formatPeso(gross);
            if (confirmFields.net) confirmFields.net.textContent = formatPeso(net);
            if (confirmFields.pending) confirmFields.pending.textContent = pending;

            if (confirmFields.pendingAlert) {
                confirmFields.pendingAlert.classList.toggle('d-none', pending <= 0);
            }

            const modalInstance = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
            modalInstance.show();
        }

        confirmSubmitBtn?.addEventListener('click', () => {
            if (pendingConfirmForm) {
                pendingConfirmForm.submit();
            }
        });

        // Process preview modal (trainer + staff processing)
        const processModalEl = document.getElementById('processPreviewModal');
        const processRangeList = document.getElementById('process-range-list');
        const processRangeSummary = document.getElementById('process-range-summary');
        const processRangeTotal = document.getElementById('process-range-total');
        const processRangeDeductions = document.getElementById('process-range-deductions');
        const processRangeCount = document.getElementById('process-range-count');
        const processDayFilter = document.getElementById('process-day-filter');
        const processConfirmBtn = document.getElementById('process-range-confirm');
        const processPreviewTitle = document.getElementById('processPreviewModalLabel');
        const processPreviewSubtitle = document.querySelector('[data-process-subtitle]');
        const processPreviewHelper = document.querySelector('[data-process-helper]');
        let pendingProcessForm = null;
        let pendingAssignments = [];
        let pendingProcessMeta = {
            role: 'trainer',
            hasSss: false,
            hasPhilhealth: false,
            hasPagibig: false,
        };

        // Ensure the preview modal lives under body to avoid overflow clipping
        if (processModalEl && processModalEl.parentElement !== document.body) {
            document.body.appendChild(processModalEl);
        }

        function getSelectedRange() {
            const normalizedRanges = getNormalizedRanges();
            if (!normalizedRanges.length) return null;

            if (processDayFilter) {
                const value = processDayFilter.value;
                if (value !== '') {
                    const idx = Number(value);
                    if (!Number.isNaN(idx) && normalizedRanges[idx]) {
                        return normalizedRanges[idx];
                    }
                }
            }

            const processMatch = normalizedRanges.find((range) => {
                const processDay = parseInt(range.process, 10);
                return Number.isInteger(processDay) && processDay === todayDay;
            });
            if (processMatch) return processMatch;

            return normalizedRanges.find((range) => {
                const from = parseInt(range.from, 10);
                const to = parseInt(range.to ?? 31, 10);
                return Number.isInteger(from) && Number.isInteger(to) && todayDay >= from && todayDay <= to;
            }) || null;
        }

        function computeProcessDeductions(gross) {
            const sssRate = Number(sssInput?.value || 0) / 100;
            const philRate = Number(philhealthInput?.value || 0) / 100;
            const pagibigRate = Number(pagibigInput?.value || 0) / 100;
            const pagibigCap = Number(pagibigCapInput?.value || 0);
            const appCutRateTrainer = Number(appCutInput?.value || 0) / 100;
            const hasSss = pendingProcessMeta?.hasSss === true;
            const hasPhilhealth = pendingProcessMeta?.hasPhilhealth === true;
            const hasPagibig = pendingProcessMeta?.hasPagibig === true;
            const isTrainer = pendingProcessMeta?.role === 'trainer';

            const sss = hasSss ? +(gross * sssRate).toFixed(2) : 0;
            const philhealth = hasPhilhealth ? +(gross * philRate).toFixed(2) : 0;
            const pagibigBase = pagibigCap > 0 ? Math.min(gross, pagibigCap) : gross;
            const pagibig = hasPagibig ? +(pagibigBase * pagibigRate).toFixed(2) : 0;
            const appCut = isTrainer ? +(gross * appCutRateTrainer).toFixed(2) : 0;
            const totalDeductions = +(sss + philhealth + pagibig + appCut).toFixed(2);

            return {
                sss,
                philhealth,
                pagibig,
                appCut,
                totalDeductions,
            };
        }

        function renderProcessAssignments() {
            if (!processRangeList) return;
            processRangeList.innerHTML = '';
            const selectedRange = getSelectedRange();
            const itemLabel = pendingProcessMeta?.role === 'staff' ? 'entry' : 'assignment';
            const emptyLabel = pendingProcessMeta?.role === 'staff' ? 'entries' : 'assignments';
            const isCompletedSessionInRange = (session) => {
                const isPayable = Number(session?.payable || 0) === 1;
                if (!isPayable) return false;
                if (!selectedRange) return true;
                const day = parseInt(session.day, 10);
                if (!Number.isInteger(day)) return true;
                return day >= (selectedRange.from || 0) && day <= (selectedRange.to || 31);
            };

            const filtered = pendingAssignments.filter((item) => {
                const timeline = Array.isArray(item.timeline) ? item.timeline : [];
                return timeline.some((session) => isCompletedSessionInRange(session));
            });

            if (processRangeSummary) {
                processRangeSummary.textContent = selectedRange
                    ? `Range ${selectedRange.from}-${selectedRange.to} → process on day ${selectedRange.process}`
                    : 'Select a processing day range';
            }
            if (!filtered.length) {
                const empty = document.createElement('div');
                empty.className = 'text-center text-muted';
                empty.textContent = `No ${emptyLabel} match the selected processing day range.`;
                processRangeList.appendChild(empty);
                if (processRangeTotal) processRangeTotal.textContent = `Gross pay ${formatPeso(0)}`;
                if (processRangeDeductions) processRangeDeductions.textContent = `Deductions ${formatPeso(0)}`;
                if (processRangeCount) processRangeCount.textContent = `0 ${emptyLabel}`;
                return;
            }

            let totalAmount = 0;

            filtered.forEach((item) => {
                const timeline = Array.isArray(item.timeline) ? item.timeline : [];
                const filteredTimeline = timeline.filter((t) => isCompletedSessionInRange(t));

                const dates = filteredTimeline.map((t) => t.label || '').filter(Boolean).join(', ');
                const totalCompleted = timeline.filter((t) => Number(t?.payable || 0) === 1).length;
                const filteredCompleted = filteredTimeline.length;
                const completedAmount = Number(item.amounts?.completed || 0);
                const amountShare = totalCompleted > 0 ? (filteredCompleted / totalCompleted) : 0;
                const amount = Number(completedAmount * amountShare);
                totalAmount += amount;

                const timelineHtml = filteredTimeline.length
                    ? filteredTimeline.map((t) => {
                        const status = (t.status || '').toLowerCase();
                        const isUpcoming = status.includes('upcoming');
                        const isPaid = status.includes('paid');
                        const isCompleted = status.includes('completed');
                        let cls = 'bg-secondary';
                        let dotColor = '#6c757d';
                        if (isUpcoming) {
                            cls = 'bg-warning text-dark';
                            dotColor = '#f59e0b';
                        } else if (isCompleted && !isPaid) {
                            cls = 'bg-danger text-white';
                            dotColor = '#dc3545';
                        } else if (isPaid || isCompleted) {
                            cls = 'bg-success';
                            dotColor = '#198754';
                        }
                        return `
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="process-dot" style="background:${dotColor};"></span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small mb-0">${t.label || '—'}</div>
                                    <span class="badge ${cls} px-2 py-1">${t.status || ''}</span>
                                </div>
                            </div>
                        `;
                    }).join('')
                    : '<div class="text-muted small">No session dates listed.</div>';

                const metaLine = item.meta
                    ? `<div class="text-muted small">${item.meta}</div>`
                    : (item.code ? `<div class="text-muted small">Code: ${item.code}</div>` : '');
                const card = document.createElement('div');
                card.className = 'process-card';
                card.innerHTML = `
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold">${item.title || 'Unnamed Schedule'}</div>
                            ${metaLine}
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success">${formatPeso(amount)}</div>
                            <div class="text-muted small">
                                ${selectedRange ? `Processing on day ${selectedRange.process}` : 'Processing day not set for this selection'}
                            </div>
                        </div>
                    </div>
                    <div class="text-muted small mt-1">${dates || 'No dates in this range'}</div>
                    <div class="process-timeline mt-2">${timelineHtml}</div>
                `;
                processRangeList.appendChild(card);
            });

            if (processRangeTotal) {
                processRangeTotal.textContent = `Gross pay ${formatPeso(totalAmount)}`;
            }
            if (processRangeDeductions) {
                const { totalDeductions } = computeProcessDeductions(totalAmount);
                processRangeDeductions.textContent = `Deductions ${formatPeso(totalDeductions)}`;
            }
            if (processRangeCount) {
                processRangeCount.textContent = `${filtered.length} ${filtered.length === 1 ? itemLabel : `${itemLabel}s`}`;
            }
        }

        document.querySelectorAll('.process-payroll-btn').forEach((btn) => {
            const previewData = btn.dataset.rangeAssignments || btn.dataset.rangeEntries;
            if (!previewData) return;
            btn.addEventListener('click', (e) => {
                const form = btn.closest('form');
                let data = [];
                try {
                    data = JSON.parse(previewData || '[]');
                } catch (err) {
                    data = [];
                }

                if (!processModalEl) return;
                e.preventDefault();
                pendingProcessForm = form;
                pendingAssignments = Array.isArray(data) ? data : [];
                const role = (btn.dataset.role || (btn.dataset.rangeAssignments ? 'trainer' : 'staff')).toLowerCase();
                if (processPreviewTitle) {
                    processPreviewTitle.textContent = role === 'staff' ? 'Entries to be processed' : 'Assignments to be processed';
                }
                if (processPreviewSubtitle) {
                    processPreviewSubtitle.textContent = role === 'staff'
                        ? 'Only completed clock-ins inside the processing window are queued.'
                        : 'Only class sessions with attendance inside the processing window are queued.';
                }
                if (processPreviewHelper) {
                    processPreviewHelper.textContent = role === 'staff'
                        ? 'Pick a range to see included entries.'
                        : 'Pick a range to see included classes.';
                }
                const parentCard = btn.closest('[data-trainer-card], [data-payroll-card]');
                pendingProcessMeta = {
                    role,
                    hasSss: parentCard?.dataset.hasSss === '1',
                    hasPhilhealth: parentCard?.dataset.hasPhilhealth === '1',
                    hasPagibig: parentCard?.dataset.hasPagibig === '1',
                };
                if (processDayFilter) {
                    const normalizedRanges = getNormalizedRanges();
                    let matchingIndex = normalizedRanges.findIndex((range) => {
                        const processDay = parseInt(range.process, 10);
                        return Number.isInteger(processDay) && processDay === todayDay;
                    });
                    if (matchingIndex < 0) {
                        matchingIndex = normalizedRanges.findIndex((range) => {
                            const from = parseInt(range.from, 10);
                            const to = parseInt(range.to ?? 31, 10);
                            return Number.isInteger(from) && Number.isInteger(to) && todayDay >= from && todayDay <= to;
                        });
                    }
                    if (matchingIndex >= 0 && processDayFilter.querySelector(`option[value="${matchingIndex}"]`)) {
                        processDayFilter.value = String(matchingIndex);
                    } else if (processDayFilter.options.length) {
                        processDayFilter.selectedIndex = 0;
                    }
                }
                renderProcessAssignments();
                const modal = bootstrap.Modal.getOrCreateInstance(processModalEl);
                modal.show();
            });
        });

        document.querySelectorAll('.process-payroll-btn').forEach((btn) => {
            if (btn.dataset.rangeAssignments || btn.dataset.rangeEntries) return;
            btn.addEventListener('click', (e) => {
                if (btn.disabled) return;
                const form = btn.closest('form');
                e.preventDefault();
                openConfirmModal(btn, form);
            });
        });

        processDayFilter?.addEventListener('change', renderProcessAssignments);
        processConfirmBtn?.addEventListener('click', () => {
            if (pendingProcessForm) {
                pendingProcessForm.submit();
            }
        });

        // Auto-open modal when redirected with trainer_modal param
        const params = new URLSearchParams(window.location.search);
        const trainerModalId = params.get('trainer_modal');
        if (trainerModalId) {
            const targetModal = document.getElementById(trainerModalId);
            if (targetModal) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(targetModal);
                modalInstance.show();
            }
        }

        const staffModalId = params.get('staff_modal');
        if (staffModalId) {
            const targetModal = document.getElementById(staffModalId);
            if (targetModal) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(targetModal);
                modalInstance.show();
            }
        }

        // Staff modal filters
        document.querySelectorAll('.staff-payroll-modal').forEach((modal) => {
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
            const rows = modal.querySelectorAll('[data-entry-row]');
            const statusSelect = modal.querySelector('[data-staff-filter-select]');
            const resetBtn = modal.querySelector('[data-staff-filter-reset]');
            const monthInput = modal.querySelector('[data-staff-filter-month]');
            const applyBtn = modal.querySelector('[data-staff-filter-apply]');
            const emptyRow = modal.querySelector('[data-staff-filter-empty]');
            const modalId = modal.getAttribute('id');

            function applyStaffFilters() {
                const status = statusSelect?.value || 'all';
                let visible = 0;

                rows.forEach((row) => {
                    const rowStatus = row.dataset.entryStatus || 'pending';
                    const show = status === 'all' || rowStatus === status;
                    row.classList.toggle('d-none', !show);
                    if (show) visible += 1;
                });

                if (emptyRow) {
                    emptyRow.classList.toggle('d-none', visible > 0);
                }
            }

            statusSelect?.addEventListener('change', applyStaffFilters);
            resetBtn?.addEventListener('click', () => {
                if (statusSelect) statusSelect.value = 'all';
                if (monthInput) monthInput.value = '';
                applyStaffFilters();
            });

            applyBtn?.addEventListener('click', () => {
                if (monthInput && monthInput.value) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('month', monthInput.value);
                    if (modalId) {
                        url.searchParams.set('staff_modal', modalId);
                    }
                    const searchInput = document.querySelector('input[name="search"]');
                    if (searchInput && searchInput.value) {
                        url.searchParams.set('search', searchInput.value);
                    }
                    window.location.href = url.toString();
                    return;
                }
                applyStaffFilters();
            });

            applyStaffFilters();
        });

        // Assignment modal filters
        document.querySelectorAll('.assignment-modal').forEach((modal) => {
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
            const cards = modal.querySelectorAll('[data-assignment-card]');
            const buttons = modal.querySelectorAll('[data-filter-button]');
            const resetBtn = modal.querySelector('[data-filter-reset]');
            const startInput = modal.querySelector('[data-filter-start]');
            const endInput = modal.querySelector('[data-filter-end]');
            const monthInput = modal.querySelector('[data-filter-month]');
            const statusSelect = modal.querySelector('[data-filter-select]');
            const rangeInput = modal.querySelector('[data-filter-range]');
            const processingRanges = Array.isArray(serverProcessingRanges) ? serverProcessingRanges : [];
            const modalId = modal.getAttribute('id');
            let activeFilter = 'all';
            const detailPanel = modal.querySelector('[data-assignment-detail]');
            let selectedCard = null;
            let selectedRange = null;
            const dateToggles = modal.querySelectorAll('[data-date-toggle]');

            dateToggles.forEach((toggle) => {
                toggle.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const row = toggle.closest('[data-assignment-card]');
                    if (!row) return;
                    const isRange = row.dataset.dateDisplay === 'range';
                    row.dataset.dateDisplay = isRange ? 'list' : 'range';
                    toggle.textContent = isRange ? 'hide' : 'see more';
                    toggle.setAttribute('aria-expanded', isRange ? 'true' : 'false');
                });
            });

            function setActive(targetFilter) {
                activeFilter = targetFilter;
                buttons.forEach((btn) => {
                    btn.classList.toggle('active', btn.dataset.filter === targetFilter);
                });
                if (statusSelect && statusSelect.value !== targetFilter) {
                    statusSelect.value = targetFilter;
                }
            }

            function renderDetail(data) {
                if (!detailPanel) return;
                if (!data) {
                    detailPanel.innerHTML = '<p class="text-muted mb-0">Select a class/schedule to view full details.</p>';
                    return;
                }

                let timeline = Array.isArray(data.timeline) ? data.timeline : [];
                if (selectedRange) {
                    timeline = timeline.filter((session) => {
                        const day = parseInt(session.day, 10);
                        if (!Number.isInteger(day)) return true;
                        return day >= (selectedRange.from || 0) && day <= (selectedRange.to || 31);
                    });
                }
                const students = Array.isArray(data.students) ? data.students : [];
                const attendance = Array.isArray(data.attendance) ? data.attendance : [];
                const counts = data.counts || {};
                const amounts = data.amounts || {};
                const series = data.series || {};

                detailPanel.innerHTML = `
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold">Class / Schedule</div>
                            <div class="fw-bold">${data.title || 'Unnamed Schedule'}</div>
                            ${data.code ? `<div class="text-muted small">Code: ${data.code}</div>` : ''}
                        </div>
                        <span class="badge ${data.category === 'Upcoming' ? 'bg-success text-white' : 'bg-secondary'}">${data.category || ''}</span>
                    </div>
                    <div class="rounded-3 border p-2 mb-2 bg-white">
                                            <div class="text-muted small text-uppercase fw-semibold mb-1">Series</div>
                                            <div class="small mb-1">Start: ${series.start || '—'}</div>
                                            <div class="small mb-1">End: ${series.end || '—'}</div>
                                            <div class="small mb-1">Recurrence: ${series.recurrence || '—'}</div>
                                            <div class="small mb-0">Hours per session: ${formatHoursWithMinutes(series.hours_per_occurrence)}</div>
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <div class="rounded-3 border p-2 bg-white h-100">
                                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Upcoming</div>
                                                    <div class="fw-bold">₱${Number(amounts.upcoming || 0).toFixed(2)}</div>
                                                    <div class="text-muted small">${counts.upcoming || 0} session(s)</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="rounded-3 border p-2 bg-white h-100">
                                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Completed</div>
                                                    <div class="fw-bold">₱${Number(amounts.completed || 0).toFixed(2)}</div>
                                                    <div class="text-muted small">${counts.completed || 0} session(s), ${counts.paid || 0} paid</div>
                                                </div>
                                            </div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small text-uppercase fw-semibold">Students</div>
                        ${students.length
                            ? `<ul class="list-unstyled small mb-0 mt-1">${students.map((s) => `<li>${s}</li>`).join('')}</ul>`
                            : '<p class="text-muted small mb-0">No students assigned.</p>'
                        }
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small text-uppercase fw-semibold">Attendance</div>
                        ${
                            timeline.length
                                ? `<ul class="list-unstyled small mb-0 mt-1">
                                    ${timeline.map((session) => `
                                        <li class="d-flex align-items-center gap-2">
                                            <span>${session.label || '—'}</span>
                                            <span class="badge ${session.status_class || 'bg-secondary'} px-2 py-1">${session.status || ''}</span>
                                        </li>
                                    `).join('')}
                                </ul>`
                                : (attendance.length
                                    ? `<ul class="list-unstyled small mb-0 mt-1">${attendance.map((a) => `<li>${a}</li>`).join('')}</ul>`
                                    : '<p class="text-muted small mb-0">No attendance recorded.</p>'
                                )
                        }
                    </div>
                    ${timeline.length ? `
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold mb-1">Series of sessions</div>
                            <div class="d-flex flex-column gap-2">
                                ${timeline.map((session, idx) => `
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="d-flex flex-column align-items-center me-1">
                                            <span class="rounded-circle bg-primary" style="width: 10px; height: 10px;"></span>
                                            ${idx < timeline.length - 1 ? '<span class="mt-1" style="width: 2px; height: 20px; background-color: #e9ecef;"></span>' : ''}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">${session.label || '—'}</div>
                                            <span class="badge ${session.status_class || 'bg-secondary'} px-2 py-1">${session.status || ''}</span>
                                            ${session.processed ? `<span class="badge bg-primary-subtle text-primary ms-1">${session.processed_label || 'Processed'}</span>` : ''}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                `;
            }

            function selectCard(card) {
                if (!card) {
                    selectedCard = null;
                    renderDetail(null);
                    return;
                }
                selectedCard = card;
                cards.forEach((c) => c.classList.remove('is-selected'));
                card.classList.add('is-selected');
                let data = null;
                try {
                    data = JSON.parse(card.dataset.detail || '{}');
                } catch (e) {
                    data = null;
                }
                renderDetail(data);
            }

            function parseDate(value) {
                if (!value) return null;
                const parsed = new Date(value);
                return Number.isNaN(parsed.getTime()) ? null : parsed;
            }

            function matchesDate(card, filterStart, filterEnd) {
                const cardStart = parseDate(card.dataset.startDate);
                const cardEnd = parseDate(card.dataset.endDate);
                const rangeStart = cardStart || cardEnd;
                const rangeEnd = cardEnd || cardStart;

                if (!filterStart && !filterEnd) {
                    return true;
                }

                if (!rangeStart && !rangeEnd) {
                    return false;
                }

                if (filterStart && rangeEnd && rangeEnd.getTime() < filterStart.getTime()) {
                    return false;
                }

                if (filterEnd && rangeStart && rangeStart.getTime() > filterEnd.getTime()) {
                    return false;
                }

                return true;
            }

            function applyFilters() {
                if (statusSelect && statusSelect.value) {
                    activeFilter = statusSelect.value;
                }

                let filterStart = parseDate(startInput?.value);
                let filterEnd = parseDate(endInput?.value);
                // If month is chosen, sync start/end to that month
                if (monthInput && monthInput.value) {
                    const [year, month] = monthInput.value.split('-').map((v) => parseInt(v, 10));
                    if (!Number.isNaN(year) && !Number.isNaN(month)) {
                        const startDate = new Date(year, month - 1, 1);
                        const endDate = new Date(year, month, 0);
                        filterStart = startDate;
                        filterEnd = endDate;
                        if (startInput) startInput.valueAsDate = startDate;
                        if (endInput) endInput.valueAsDate = endDate;
                    }
                }

                const rangeIndex = rangeInput?.value;
                selectedRange = (typeof rangeIndex !== 'undefined' && rangeIndex !== '') ? processingRanges?.[Number(rangeIndex)] : null;
                let visible = 0;
                const aggregates = {
                    future: { salary: 0, count: 0, payrollCount: 0 },
                    past: { salary: 0, count: 0, payrollCount: 0 },
                };

                cards.forEach((card) => {
                    const category = card.dataset.category || 'all';
                    const futureCount = Number(card.dataset.futureCount || 0);
                    const pastCount = Number(card.dataset.pastCount || 0);
                    const paidCount = Number(card.dataset.paidCount || 0);
                    const hasFuture = futureCount > 0;
                    const hasPast = pastCount > 0 || paidCount > 0;

                    let attendanceMatch = true;
                    if (activeFilter === 'present' || activeFilter === 'absent') {
                        const attendanceValues = (card.dataset.attendance || '')
                            .split(',')
                            .map((value) => value.trim())
                            .filter(Boolean);
                        attendanceMatch = attendanceValues.includes(activeFilter);
                    }

                    const dateMatch = matchesDate(card, filterStart, filterEnd);
                    let rangeMatch = true;
                    if (selectedRange) {
                        const days = (card.dataset.occurrenceDays || '')
                            .split(',')
                            .map((v) => parseInt(v, 10))
                            .filter((v) => Number.isInteger(v));
                        const fallbackDate = parseDate(card.dataset.startDate);
                        const fallbackDay = fallbackDate ? fallbackDate.getDate() : null;
                        const daysToCheck = days.length ? days : (fallbackDay ? [fallbackDay] : []);
                        rangeMatch = daysToCheck.some((day) => day >= (selectedRange.from || 0) && day <= (selectedRange.to || 31));
                    }

                    const show = attendanceMatch && dateMatch && rangeMatch;
                    card.classList.toggle('d-none', !show);
                    if (show) {
                        visible += 1;
                        let detailData = null;
                        try {
                            detailData = JSON.parse(card.dataset.detail || '{}');
                        } catch (e) {
                            detailData = null;
                        }

                        let timeline = Array.isArray(detailData?.timeline) ? detailData.timeline : [];
                        const counts = detailData?.counts || {};
                        const amounts = detailData?.amounts || {};
                        if (selectedRange) {
                            timeline = timeline.filter((session) => {
                                const day = parseInt(session.day, 10);
                                if (!Number.isInteger(day)) return true;
                                return day >= (selectedRange.from || 0) && day <= (selectedRange.to || 31);
                            });
                        }
                        const filteredTimeline = selectedRange
                            ? timeline.filter((session) => {
                                const day = parseInt(session.day, 10);
                                if (!Number.isInteger(day)) return true;
                                return day >= (selectedRange.from || 0) && day <= (selectedRange.to || 31);
                            })
                            : timeline;
                        const upcomingSessions = filteredTimeline.filter((session) => (session.status || '').toLowerCase().includes('upcoming')).length;
                        const completedSessions = filteredTimeline.filter((session) => (session.status || '').toLowerCase().includes('completed')).length;
                        const paidSessions = filteredTimeline.filter((session) => (session.status || '').toLowerCase().includes('paid')).length;

                        const upcomingTotal = Number(amounts.upcoming || 0);
                        const completedTotal = Number(amounts.completed || 0);
                        const totalUpcomingSessions = Number(counts.upcoming || 0);
                        const totalCompletedSessions = Number(counts.completed || 0);

                        const upcomingShare = totalUpcomingSessions > 0 ? (upcomingSessions / totalUpcomingSessions) : 0;
                        const completedShare = totalCompletedSessions > 0 ? (completedSessions / totalCompletedSessions) : 0;

                        const futureSalary = upcomingTotal * upcomingShare;
                        const pastSalary = completedTotal * completedShare;

                        if (upcomingSessions > 0) {
                            aggregates.future.salary += futureSalary;
                            aggregates.future.count += upcomingSessions;
                            aggregates.future.payrollCount += upcomingSessions;
                        }
                        if (completedSessions > 0) {
                            aggregates.past.salary += pastSalary;
                            aggregates.past.count += completedSessions;
                            aggregates.past.payrollCount += paidSessions || completedSessions;
                        }
                    }
                });

                // Hide session rows outside selected range in the timeline lists
                cards.forEach((card) => {
                    const timelineItems = card.querySelectorAll('[data-session-day]');
                    if (!timelineItems.length) return;
                    timelineItems.forEach((item) => {
                        if (!selectedRange) {
                            item.classList.remove('d-none');
                            return;
                        }
                        const day = parseInt(item.dataset.sessionDay || item.getAttribute('data-session-day') || '', 10);
                        const match = Number.isInteger(day)
                            ? day >= (selectedRange.from || 0) && day <= (selectedRange.to || 31)
                            : true;
                        item.classList.toggle('d-none', !match);
                    });
                });

                // Show a helper message if nothing matches
                let empty = modal.querySelector('.assignment-empty');
                if (!empty) {
                    empty = document.createElement('p');
                    empty.className = 'assignment-empty text-muted small mt-2';
                    empty.textContent = 'No assignments match this filter.';
                    modal.querySelector('.assignment-list')?.appendChild(empty);
                }
                empty.classList.toggle('d-none', visible > 0);

                // Update totals per category
                const futureTotalEl = modal.querySelector('[data-total-future]');
                const futureCountEl = modal.querySelector('[data-count-future]');
                const futurePayrollCountEl = modal.querySelector('[data-payroll-count-future]');
                const pastTotalEl = modal.querySelector('[data-total-past]');
                const pastCountEl = modal.querySelector('[data-count-past]');
                const pastPayrollCountEl = modal.querySelector('[data-payroll-count-past]');

                if (futureTotalEl) futureTotalEl.textContent = `₱${aggregates.future.salary.toFixed(2)}`;
                if (futureCountEl) {
                    futureCountEl.textContent = `${aggregates.future.count} ${aggregates.future.count === 1 ? 'assignment' : 'assignments'}`;
                    futureCountEl.dataset.countFuture = aggregates.future.count;
                }
                if (futurePayrollCountEl) {
                    futurePayrollCountEl.textContent = `${aggregates.future.payrollCount} payroll ${aggregates.future.payrollCount === 1 ? 'class' : 'classes'}`;
                }

                if (pastTotalEl) pastTotalEl.textContent = `₱${aggregates.past.salary.toFixed(2)}`;
                if (pastCountEl) {
                    pastCountEl.textContent = `${aggregates.past.count} ${aggregates.past.count === 1 ? 'assignment' : 'assignments'}`;
                    pastCountEl.dataset.countPast = aggregates.past.count;
                }
                if (pastPayrollCountEl) {
                    pastPayrollCountEl.textContent = `${aggregates.past.payrollCount} payroll ${aggregates.past.payrollCount === 1 ? 'class' : 'classes'}`;
                }

                if (!selectedCard || selectedCard.classList.contains('d-none')) {
                    const firstVisible = Array.from(cards).find((card) => !card.classList.contains('d-none'));
                    selectCard(firstVisible || null);
                }
            }

            buttons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const filter = btn.dataset.filter || 'all';
                    setActive(filter);
                    applyFilters();
                });
            });

            statusSelect?.addEventListener('change', () => {
                setActive(statusSelect.value || 'all');
                applyFilters();
            });

            [startInput, endInput].forEach((input) => {
                input?.addEventListener('change', applyFilters);
            });

            resetBtn?.addEventListener('click', () => {
                setActive('all');
                if (startInput) startInput.value = '';
                if (endInput) endInput.value = '';
                if (monthInput) monthInput.value = '';
                if (rangeInput) rangeInput.value = '';
                applyFilters();
            });

            monthInput?.addEventListener('change', applyFilters);
            rangeInput?.addEventListener('change', applyFilters);
            const monthApplyBtn = modal.querySelector('[data-filter-month-apply]');
            monthApplyBtn?.addEventListener('click', () => {
                if (!monthInput || !monthInput.value) return;
                const url = new URL(window.location.href);
                url.searchParams.set('month', monthInput.value);
                url.searchParams.set('trainer_modal', modalId);
                // Persist search input from the main form if present
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput && searchInput.value) {
                    url.searchParams.set('search', searchInput.value);
                }
                window.location.href = url.toString();
            });

            cards.forEach((card) => {
                card.addEventListener('click', () => selectCard(card));
            });

            // Default state
            if (statusSelect && statusSelect.value) {
                setActive(statusSelect.value);
            }
            applyFilters();
            const firstVisible = Array.from(cards).find((card) => !card.classList.contains('d-none'));
            selectCard(firstVisible || null);
        });
    });
</script>
@endsection
