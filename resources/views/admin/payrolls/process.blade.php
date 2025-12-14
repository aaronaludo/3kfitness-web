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
                                <h3 class="display-6 fw-bold mb-1">₱{{ number_format($stats['projected_net'], 2) }}</h3>
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

            <section id="staff-payroll-section" class="payroll-section">
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
                    <div class="col-12 col-md-3">
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
                    <div class="col-12 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Pending clock-outs</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-hourglass-half text-warning fs-4"></i>
                                    <span class="fs-4 fw-bold">{{ $stats['pending_entries'] }}</span>
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
                                    <span class="fs-4 fw-bold">{{ number_format($stats['total_hours'], 2) }} hrs</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="text-muted small text-uppercase fw-semibold">Net payout</div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <i class="fa-solid fa-peso-sign text-success fs-4"></i>
                                    <span class="fs-4 fw-bold">₱{{ number_format($stats['projected_net'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                @forelse ($summaries as $summary)
                    @php
                        $staff = $summary['staff'];
                        $collapseId = 'payroll-breakdown-' . $staff->id;
                    @endphp
                    <div
                        class="card border-0 shadow-sm rounded-4 mb-3"
                        data-payroll-card
                        data-gross="{{ $summary['gross_pay'] }}"
                        data-rate="{{ $staff->rate_per_hour ?? 0 }}"
                        data-appcut="{{ $summary['deductions']['app_cut'] ?? 0 }}"
                    >
                        <div class="card-body p-4">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                <div>
                                    <h5 class="fw-semibold mb-1">{{ $staff->first_name }} {{ $staff->last_name }}</h5>
                                    <div class="text-muted small">{{ $staff->email }}</div>
                                    <span class="badge bg-light text-dark fw-semibold rounded-pill px-3 py-2 mt-2">
                                        ₱{{ number_format($staff->rate_per_hour ?? 0, 2) }} / hr
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="text-start">
                                        <div class="text-muted small text-uppercase">Hours</div>
                                        <div class="fw-bold fs-5">{{ number_format($summary['total_hours'], 2) }}</div>
                                        @if(!empty($summary['processed_run']))
                                            <div class="text-muted small">Processed: {{ number_format($summary['processed_run']->total_hours, 2) }} hrs</div>
                                        @endif
                                    </div>
                                    <div class="text-start">
                                        <div class="text-muted small text-uppercase">Gross</div>
                                        <div class="fw-bold fs-5">₱{{ number_format($summary['gross_pay'], 2) }}</div>
                                        @if(!empty($summary['processed_run']))
                                            <div class="text-muted small">Processed: ₱{{ number_format($summary['processed_run']->gross_pay, 2) }}</div>
                                        @endif
                                    </div>
                                    <div class="text-start">
                                        <div class="text-muted small text-uppercase">Net</div>
                                        <div class="fw-bold fs-5 text-success" data-net>₱{{ number_format($summary['net_pay'], 2) }}</div>
                                        @if(!empty($summary['processed_run']))
                                            <div class="text-muted small">Processed: ₱{{ number_format($summary['processed_run']->net_pay, 2) }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        @if(!empty($summary['processed_run']))
                                            <span class="badge bg-secondary rounded-pill px-3 py-2">Processed</span>
                                        @else
                                            <span class="badge {{ $summary['pending_entries'] ? 'bg-warning text-dark' : 'bg-success' }} rounded-pill px-3 py-2">
                                                {{ $summary['pending_entries'] ? $summary['pending_entries'] . ' pending entries' : 'Ready to finalize' }}
                                            </span>
                                        @endif
                                    </div>
                                    @php
                                        $staffProcessDisabled = $summary['pending_entries'] || !empty($summary['processed_run']);
                                        $staffProcessTitle = $summary['pending_entries']
                                            ? 'Clock-out pending entries before processing'
                                            : (!empty($summary['processed_run']) ? 'Already processed for this period' : 'Process and save payroll');
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
                                            {{ $staffProcessDisabled ? 'disabled' : '' }}
                                            title="{{ $staffProcessTitle }}"
                                        >
                                            <i class="fa-solid fa-circle-check"></i>
                                            {{ !empty($summary['processed_run']) ? 'Processed' : 'Process payroll' }}
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

                                    $payslipData = [
                                        'type' => 'staff',
                                        'name' => $staff->first_name . ' ' . $staff->last_name,
                                        'email' => $staff->email,
                                        'rate' => $staff->rate_per_hour ?? 0,
                                        'gross' => $summary['gross_pay'],
                                        'net' => $summary['net_pay'],
                                            'deductions' => $summary['deductions'],
                                            'month' => $monthLabel,
                                            'entries' => $printEntries,
                                        ];

                                        $payslipJson = json_encode($payslipData);
                                    @endphp
                                    @if(empty($summary['processed_run']))
                                        <button
                                            type="button"
                                            class="btn btn-danger rounded-pill px-3 d-flex align-items-center gap-2 payslip-btn"
                                            data-payslip='{{ $payslipJson }}'
                                        >
                                            <i class="fa-solid fa-file-pdf"></i>
                                            Print payslip
                                        </button>
                                    @endif
                                    <button
                                        class="btn btn-outline-primary rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $collapseId }}"
                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                        aria-controls="{{ $collapseId }}"
                                    >
                                        Review details
                                    </button>
                                </div>
                            </div>

                            <div class="collapse {{ $loop->first ? 'show' : '' }} mt-3" id="{{ $collapseId }}">
                                <div class="row g-3">
                                    <div class="col-12 col-lg-4">
                                        <div class="border rounded-4 p-3 h-100 bg-light">
                                            <h6 class="fw-semibold mb-3">Payroll summary</h6>
                                            <ul class="list-unstyled small mb-0">
                                                <li class="d-flex justify-content-between mb-2">
                                                    <span>Gross pay</span>
                                                    <span>₱{{ number_format($summary['gross_pay'], 2) }}</span>
                                                </li>
                                                <li class="d-flex justify-content-between mb-2">
                                                    <span>SSS</span>
                                                    <span data-sss>₱{{ number_format($summary['deductions']['sss'], 2) }}</span>
                                                </li>
                                                <li class="d-flex justify-content-between mb-2">
                                                    <span>PhilHealth</span>
                                                    <span data-philhealth>₱{{ number_format($summary['deductions']['philhealth'], 2) }}</span>
                                                </li>
                                                <li class="d-flex justify-content-between mb-2">
                                                    <span>Pag-IBIG</span>
                                                    <span data-pagibig>₱{{ number_format($summary['deductions']['pagibig'], 2) }}</span>
                                                </li>
                                                <li class="d-flex justify-content-between mb-2">
                                                    <span>3kfitness app cut</span>
                                                    <span data-appcut>₱{{ number_format($summary['deductions']['app_cut'] ?? 0, 2) }}</span>
                                                </li>
                                                <li class="d-flex justify-content-between fw-semibold pt-2 border-top">
                                                    <span>Net pay</span>
                                                    <span data-net>₱{{ number_format($summary['net_pay'], 2) }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-8">
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th scope="col">Entry ID</th>
                                                        <th scope="col">Clock in</th>
                                                        <th scope="col">Clock out</th>
                                                        <th scope="col">Hours</th>
                                                        <th scope="col">Amount</th>
                                                        <th scope="col">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($summary['entries'] as $entry)
                                                        <tr>
                                                            <td class="text-muted">#{{ $entry['id'] }}</td>
                                                            <td>
                                                                {{ $entry['clockin_at'] ? $entry['clockin_at']->format('M d, Y g:i A') : '—' }}
                                                            </td>
                                                            <td>
                                                                {{ $entry['clockout_at'] ? $entry['clockout_at']->format('M d, Y g:i A') : '—' }}
                                                            </td>
                                                            <td>{{ $entry['hours'] ? number_format($entry['hours'], 2) . ' hrs' : 'Pending' }}</td>
                                                            <td>
                                                                {{ $entry['amount'] ? '₱' . number_format($entry['amount'], 2) : '—' }}
                                                            </td>
                                                            <td>
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
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body text-center py-5">
                            <h5 class="fw-semibold mb-2">No payroll data found</h5>
                            <p class="text-muted mb-3">Try selecting a different month or adjusting your search filters.</p>
                            <a href="{{ route('admin.payrolls.index') }}" class="btn btn-danger rounded-pill px-4">Go back to payroll list</a>
                        </div>
                    </div>
                @endforelse
            </div>
            </section>

            <section id="trainer-payroll-section" class="payroll-section mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Trainer Payroll</span>
                                <h4 class="fw-semibold mb-1">Assignments & earnings</h4>
                                <p class="text-muted mb-0">Review trainer class assignments, durations, and estimated payouts using the same streamlined layout.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">{{ ($trainerAssignments ?? collect())->count() }} trainers with assignments</span>
                            </div>
                        </div>

                        @forelse($trainerAssignments as $assignment)
                            @php
                                $trainer = $assignment['trainer'];
                                $modalId = 'trainer-assignments-' . $trainer->id;
                                $totals = $assignment['totals'];
                                $processedRun = $assignment['processed_run'] ?? null;
                                $trainerGross = $processedRun->gross_pay ?? ($assignment['payable_salary'] ?? 0);
                                $trainerProjectedGross = $assignment['total_salary'] ?? 0;
                                $trainerUpcoming = $totals['future_total'] ?? 0;
                                $trainerSss = $processedRun->deduction_sss ?? ($assignment['deductions']['sss'] ?? round($trainerGross * 0.045, 2));
                                $trainerPhilhealth = $processedRun->deduction_philhealth ?? ($assignment['deductions']['philhealth'] ?? round($trainerGross * 0.025, 2));
                                $trainerPagibig = $processedRun->deduction_pagibig ?? ($assignment['deductions']['pagibig'] ?? round(min($trainerGross, 5000) * 0.02, 2));
                                $trainerAppCut = $assignment['deductions']['app_cut'] ?? 0;
                                $trainerNet = $processedRun->net_pay ?? $assignment['net_pay'];
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
                                            'date' => $paidDates->isNotEmpty()
                                                ? $paidDates->implode(', ')
                                                : ($start ? $start->format('M d, Y') : '—'),
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
                                    'gross' => $trainerGross,
                                    'net' => $trainerNet,
                                    'deductions' => [
                                        'sss' => $trainerSss,
                                        'philhealth' => $trainerPhilhealth,
                                        'pagibig' => $trainerPagibig,
                                        'app_cut' => $trainerAppCut,
                                    ],
                                    'month' => $monthLabel,
                                    'assignments' => $attendanceAssignments,
                                ];
                                $trainerPayslipJson = json_encode($trainerPayslipData);
                                $canProcessTrainer = ($assignment['payable_assignments_count'] ?? 0) > 0 && empty($processedRun);
                            @endphp
                            <div
                                class="card border-0 shadow-sm rounded-4 mb-3"
                                data-trainer-card
                                data-gross="{{ $trainerGross }}"
                                data-sss="{{ $trainerSss }}"
                                data-philhealth="{{ $trainerPhilhealth }}"
                                data-pagibig="{{ $trainerPagibig }}"
                                data-appcut="{{ $trainerAppCut }}"
                                data-net="{{ $trainerNet }}"
                            >
                                <div class="card-body p-4">
                                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                        <div>
                                            <h5 class="fw-semibold mb-1">{{ $trainer->first_name }} {{ $trainer->last_name }}</h5>
                                            <div class="text-muted small">{{ $trainer->email }}</div>
                                            <span class="badge bg-light text-dark fw-semibold rounded-pill px-3 py-2 mt-2">
                                                Assignments: {{ $assignment['assignments_count'] }}
                                            </span>
                                        </div>
                                        <div class="d-flex flex-wrap align-items-center gap-3">
                                            <div class="text-start">
                                                <div class="text-muted small text-uppercase">Payable classes</div>
                                                <div class="fw-bold fs-5">{{ $assignment['payable_assignments_count'] }}</div>
                                            </div>
                                            <div class="text-start">
                                                <div class="text-muted small text-uppercase">Projected total (incl. upcoming)</div>
                                                <div class="fw-bold fs-5">₱{{ number_format($trainerProjectedGross, 2) }}</div>
                                                <div class="text-muted small">Upcoming: ₱{{ number_format($trainerUpcoming, 2) }}</div>
                                            </div>
                                            <div class="text-start">
                                                <div class="text-muted small text-uppercase">Net (after deductions)</div>
                                                <div class="fw-bold fs-6 text-success" data-net>₱{{ number_format($trainerNet, 2) }}</div>
                                                <div class="text-muted small">Completed classes only</div>
                                            </div>
                                            @php
                                                $trainerProcessDisabled = !$canProcessTrainer;
                                                $trainerProcessTitle = $canProcessTrainer
                                                    ? 'Process and save payroll'
                                                    : (!empty($processedRun) ? 'Already processed for this period' : 'No completed assignments for this period');
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
                                                    {{ $trainerProcessDisabled ? 'disabled' : '' }}
                                                    title="{{ $trainerProcessTitle }}"
                                                >
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    {{ !empty($processedRun) ? 'Processed' : 'Process payroll' }}
                                                </button>
                                            </form>
                                            @if(empty($processedRun))
                                                <button
                                                    type="button"
                                                    class="btn btn-danger rounded-pill px-3 d-flex align-items-center gap-2 payslip-btn"
                                                    data-payslip='{{ $trainerPayslipJson }}'
                                                >
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                    Print payslip
                                                </button>
                                            @endif
                                            <button
                                                class="btn btn-outline-primary rounded-pill px-3"
                                                type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#{{ $modalId }}"
                                            >
                                                View assignments
                                            </button>
                                            @if(!empty($processedRun))
                                                <span class="badge bg-secondary rounded-pill px-3 py-2">Processed</span>
                                            @elseif(($assignment['payable_assignments_count'] ?? 0) === 0)
                                                <span class="badge bg-warning text-dark rounded-pill px-3 py-2">No completed assignments</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal fade assignment-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
                                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header align-items-center">
                                            <div>
                                                <h5 class="modal-title fw-semibold mb-0" id="{{ $modalId }}Label">Assignments for {{ $trainer->first_name }} {{ $trainer->last_name }}</h5>
                                                <span class="text-muted small">Total estimated: ₱{{ number_format($trainerProjectedGross, 2) }}</span>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                                <span class="badge bg-dark text-white rounded-pill px-3 py-2">Payable gross: ₱{{ number_format($trainerGross, 2) }}</span>
                                                <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">Upcoming estimate: ₱{{ number_format($trainerUpcoming, 2) }}</span>
                                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">Completed classes: {{ $assignment['payable_assignments_count'] }}</span>
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2">Projected classes: {{ $assignment['salary_assignments_count'] }}</span>
                                                <span class="badge bg-light text-muted rounded-pill px-3 py-2">Hours (completed): {{ number_format($assignment['total_hours'], 2) }}</span>
                                            </div>
                                            <div class="row g-3 mb-3">
                                                <div class="col-12 col-md-6">
                <div class="border rounded-4 p-3 h-100 bg-light">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success text-white rounded-circle p-2"><i class="fa-solid fa-calendar-check"></i></span>
                            <span class="text-muted small text-uppercase fw-semibold">Upcoming</span>
                        </div>
                        <span class="text-muted small" data-count-future="{{ $totals['future_count'] }}">{{ $totals['future_count'] }} {{ $totals['future_count'] === 1 ? 'assignment' : 'assignments' }}</span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between mt-2">
                        <span class="fs-5 fw-semibold" data-total-future>₱{{ number_format($totals['future_total'], 2) }}</span>
                        <span class="badge bg-success-subtle text-success rounded-pill px-3" data-payroll-count-future>{{ $totals['future_payroll_count'] }} payroll {{ $totals['future_payroll_count'] === 1 ? 'class' : 'classes' }}</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="border rounded-4 p-3 h-100 bg-light">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary text-white rounded-circle p-2"><i class="fa-solid fa-clipboard-check"></i></span>
                            <span class="text-muted small text-uppercase fw-semibold">Completed</span>
                        </div>
                        <span class="text-muted small" data-count-past="{{ $totals['past_count'] }}">{{ $totals['past_count'] }} {{ $totals['past_count'] === 1 ? 'assignment' : 'assignments' }}</span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between mt-2">
                        <span class="fs-5 fw-semibold" data-total-past>₱{{ number_format($totals['past_total'], 2) }}</span>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3" data-payroll-count-past>{{ $totals['past_payroll_count'] }} payroll {{ $totals['past_payroll_count'] === 1 ? 'class' : 'classes' }}</span>
                    </div>
                </div>
            </div>
        </div>

                                            <div class="row g-3 mb-3">
                                                <div class="col-12 col-md-6">
                                                    <div class="border rounded-4 p-3 h-100 bg-light">
                                                        <span class="text-muted small text-uppercase fw-semibold d-block">Deductions</span>
                                                        <ul class="list-unstyled small mb-0">
                                                            <li class="d-flex justify-content-between">
                                                                <span>SSS</span>
                                                                <span data-sss>₱{{ number_format($trainerSss, 2) }}</span>
                                                            </li>
                                                            <li class="d-flex justify-content-between">
                                                                <span>PhilHealth</span>
                                                                <span data-philhealth>₱{{ number_format($trainerPhilhealth, 2) }}</span>
                                                            </li>
                                                            <li class="d-flex justify-content-between">
                                                                <span>Pag-IBIG</span>
                                                                <span data-pagibig>₱{{ number_format($trainerPagibig, 2) }}</span>
                                                            </li>
                                                            <li class="d-flex justify-content-between">
                                                                <span>3kfitness app cut</span>
                                                                <span data-appcut>₱{{ number_format($trainerAppCut, 2) }}</span>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-md-6 d-flex align-items-center">
                                                    <div class="w-100 border rounded-4 p-3 bg-light">
                                                        <div class="d-flex justify-content-between">
                                                            <span class="text-muted small text-uppercase fw-semibold">Gross (completed classes)</span>
                                                            <span>₱{{ number_format($trainerGross, 2) }}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between text-muted small mt-1">
                                                            <span>Upcoming not included</span>
                                                            <span>₱{{ number_format($trainerUpcoming, 2) }}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between mt-2">
                                                            <span class="fw-semibold">Net payable</span>
                                                            <span class="fw-bold text-success" data-net>₱{{ number_format($trainerNet, 2) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card border-0 shadow-sm bg-light mb-3">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge bg-primary-subtle text-primary rounded-circle p-2"><i class="fa-solid fa-filter"></i></span>
                                                            <span class="text-muted small text-uppercase fw-semibold">Refine assignments</span>
                                                        </div>
                                                        <button type="button" class="btn btn-link btn-sm text-decoration-none px-0" data-filter-reset>Reset filters</button>
                                                    </div>
                                                    <div class="row g-2 align-items-end">
                                                        <div class="col-12 col-lg-4">
                                                            <label class="form-label text-muted text-uppercase small mb-1">Category</label>
                                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                                <button type="button" class="btn btn-outline-secondary active" data-filter-button data-filter="all">All</button>
                                                                <button type="button" class="btn btn-outline-secondary" data-filter-button data-filter="future">Upcoming</button>
                                                                <button type="button" class="btn btn-outline-secondary" data-filter-button data-filter="past">Completed</button>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-sm-4 col-lg-3">
                                                            <label class="form-label text-muted text-uppercase small mb-1">Month</label>
                                                            <input type="month" class="form-control form-control-sm" data-filter-month>
                                                        </div>
                                                        <div class="col-12 col-sm-4 col-lg-3">
                                                            <label class="form-label text-muted text-uppercase small mb-1">Processing day range</label>
                                                            @php $ranges = $deductionSettings['processing_day_ranges'] ?? []; @endphp
                                                            <select class="form-select form-select-sm" data-filter-range {{ empty($ranges) ? 'disabled' : '' }}>
                                                                <option value="">All ranges</option>
                                                                @foreach($ranges as $idx => $range)
                                                                    <option value="{{ $idx }}">{{ $range['from'] ?? '?' }}-{{ $range['to'] ?? '?' }} → {{ $range['process'] ?? '?' }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-12 col-sm-4 col-lg-3">
                                                            <label class="form-label text-muted text-uppercase small mb-1">From date</label>
                                                            <input type="date" class="form-control form-control-sm" data-filter-start>
                                                        </div>
                                                        <div class="col-12 col-sm-4 col-lg-3">
                                                            <label class="form-label text-muted text-uppercase small mb-1">To date</label>
                                                            <input type="date" class="form-control form-control-sm" data-filter-end>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <div class="assignment-list">
                    @foreach($assignmentDetails as $detail)
                        @php
                            $schedule = $detail['schedule'];
                            $start = $detail['start'];
                            $end = $detail['end'];
                            $category = $detail['category'];
                            $categoryLabel = $category === 'future' ? 'Upcoming' : 'Completed';
                            $badgeClass = $category === 'future' ? 'bg-success text-white' : 'bg-secondary';
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
                            $occurrenceDates = $occurrenceDatesRaw->map(function ($date) {
                                try {
                                    return \Carbon\Carbon::parse($date)->format('M d');
                                } catch (\Throwable $th) {
                                    return $date;
                                }
                            })->values();
                    $startFilterDate = $occurrenceDatesRaw->first() ?? ($detail['start_date'] ?? '');
                    $endFilterDate = $occurrenceDatesRaw->last() ?? ($detail['end_date'] ?? '');
                    $occurrenceDays = $occurrenceDatesRaw->map(function ($date) {
                        try {
                            return \Carbon\Carbon::parse($date)->day;
                        } catch (\Throwable $th) {
                            return null;
                        }
                    })->filter()->values();
                            $occurrenceTimeline = collect($detail['occurrence_dates'] ?? [])
                                ->map(function ($date) use ($detail) {
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
                        @endphp
                        <div
                            class="border rounded-3 p-3 mb-3 assignment-card"
                            style="cursor: pointer;"
                            data-assignment-card
                            data-category="{{ $category }}"
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
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="badge bg-dark-subtle text-dark rounded-circle p-2"><i class="fa-solid fa-dumbbell"></i></span>
                                    <div>
                                        <h6 class="mb-1">{{ $schedule->name ?? 'Unnamed Schedule' }}</h6>
                                        <div class="d-flex flex-wrap gap-2 mt-1">
                                            @if(!empty($schedule->class_code))
                                                <span class="badge bg-light text-muted border">Code: {{ $schedule->class_code }}</span>
                                            @endif
                                            @if($detail['hours'] > 0)
                                                <span class="badge bg-primary-subtle text-primary">Duration: {{ number_format($detail['hours'], 2) }} hrs</span>
                                            @endif
                                            @if(!is_null($schedule->trainer_rate_per_hour))
                                                <span class="badge bg-success-subtle text-success">Rate: ₱{{ number_format((float) $schedule->trainer_rate_per_hour, 2) }}/hr</span>
                                            @endif
                                            @if($category === 'past')
                                                <span class="badge {{ $hasAttendance ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning' }}">
                                                    Payable: ₱{{ number_format($payableSalary, 2) }}
                                                </span>
                                            @elseif($detail['display_salary'] > 0)
                                                <span class="badge bg-danger-subtle text-danger">Est. salary: ₱{{ number_format($detail['display_salary'], 2) }}</span>
                                            @endif
                                            @if($category === 'past')
                                                @if($hasAttendance)
                                                    <span class="badge bg-success-subtle text-success">Attendance logged</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Absent (no attendance)</span>
                                                @endif
                                            @endif
                                            @if($recurringLabel)
                                                <span class="badge bg-info-subtle text-info">Recurring: {{ $recurringLabel }}</span>
                                            @endif
                                            @if($occurrenceDates->isNotEmpty())
                                                <span class="badge bg-light text-muted border">
                                                    Dates: {{ $occurrenceDates->take(3)->implode(', ') }}@if($occurrenceDates->count() > 3)+{{ $occurrenceDates->count() - 3 }} more @endif
                                                </span>
                                            @endif
                                        </div>
                                        @if($start || $end)
                                            <span class="text-muted small d-block mt-1">
                                                {{ $rangeStart }}
                                                @if($rangeEnd)
                                                    &ndash; {{ $rangeEnd }}
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <span class="badge {{ $badgeClass }}">{{ $categoryLabel }}</span>
                            </div>
                            <div class="mt-3">
                                <span class="text-muted small text-uppercase fw-semibold">Students</span>
                                @if($students->isNotEmpty())
                                    <ul class="list-unstyled mb-0 small mt-1">
                                        @foreach($students as $student)
                                            <li>{{ $student }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted small mb-0">No students assigned.</p>
                                @endif
                            </div>
                            <div class="mt-3">
                                <span class="text-muted small text-uppercase fw-semibold">Attendance</span>
                                @if($attendanceRecords->isNotEmpty())
                                    <ul class="list-unstyled mb-0 small mt-1">
                                        @foreach($attendanceRecords as $record)
                                            @php
                                                $clockIn = $record['clockin_at'] ?? null;
                                                $clockOut = $record['clockout_at'] ?? null;
                                                $clockInLabel = $clockIn ? $clockIn->format('M d, Y g:i A') : '—';
                                                $clockOutLabel = $clockOut ? $clockOut->format('M d, Y g:i A') : null;
                                            @endphp
                                            <li>
                                                {{ $clockInLabel }}
                                                @if($clockOutLabel)
                                                    – {{ $clockOutLabel }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted small mb-0">No attendance recorded for this schedule.</p>
                                @endif
                            </div>
                            @if($occurrenceTimeline->isNotEmpty())
                                <div class="mt-3">
                                    <span class="text-muted small text-uppercase fw-semibold d-block mb-1">Series of sessions</span>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach($occurrenceTimeline as $index => $session)
                                            <div class="d-flex align-items-start gap-2" data-session-day="{{ $session['day'] ?? '' }}">
                                                <div class="d-flex flex-column align-items-center me-1">
                                                    <span class="rounded-circle bg-primary" style="width: 10px; height: 10px;"></span>
                                                    @if($index < $occurrenceTimeline->count() - 1)
                                                        <span class="mt-1" style="width: 2px; height: 20px; background-color: #e9ecef;"></span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $session['label'] }}</div>
                                                    <span class="badge {{ $session['status_class'] }} px-2 py-1">{{ $session['status'] }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="border rounded-4 p-3 h-100 bg-light" data-assignment-detail>
                    <p class="text-muted mb-0">Select a class/schedule to view full details.</p>
                </div>
            </div>
        </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted">No trainer assignments found for this period.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        </section>
    </div>

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

    {{-- Process preview modal --}}
    <div class="modal fade" id="processPreviewModal" tabindex="-1" aria-labelledby="processPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-sm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-semibold mb-0" id="processPreviewModalLabel">Assignments to be processed</h5>
                        <p class="text-muted small mb-0">Filtered by processing day range.</p>
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
                                <div class="text-muted small mb-0">Using today's date to select the matching processing range.</div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <span class="pill-badge info" id="process-range-summary">Showing all scheduled days</span>
                                <span class="pill-badge success" id="process-range-total">₱0.00 total</span>
                                <span class="pill-badge" id="process-range-count">0 assignments</span>
                            </div>
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
        const toggleButtons = document.querySelectorAll('[data-payroll-toggle]');
        const staffSection = document.getElementById('staff-payroll-section');
        const trainerSection = document.getElementById('trainer-payroll-section');

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

        function normalizeRanges() {
            processingRanges = processingRanges
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
                        normalizeRanges();
                        renderRangeRows();
                        renderRangePreview();
                        updateProcessButtons();
                    });
                });
                node.querySelector('[data-remove-range]')?.addEventListener('click', () => {
                    processingRanges.splice(index, 1);
                    normalizeRanges();
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
            const ranges = processingRanges.length ? processingRanges : [];
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
            const rangeDays = processingRanges.map((r) => r.process).filter((v, i, arr) => arr.indexOf(v) === i);
            const explicitDays = activationDays;
            return [...new Set([...explicitDays, ...rangeDays])];
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
        }

        normalizeActivationDays();
        normalizeRanges();
        if (!processingRanges.length) {
            processingRanges = defaultRanges();
        }

        addRangeBtn?.addEventListener('click', () => {
            processingRanges.push({ from: '', to: '', process: '' });
            normalizeRanges();
            renderRangeRows();
            renderRangePreview();
            updateProcessButtons();
        });

        updateProcessButtons();
        renderRangeRows();
        renderRangePreview();

        const buttons = document.querySelectorAll('[data-payslip]');

        buttons.forEach((btn) => {
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
                const isTrainer = data.type === 'trainer';
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
                    </style>
                `;

                const rows = entries.map((entry) => {
                    const status = entry.status === 'complete'
                        ? '<span class="badge badge-success">Complete</span>'
                        : '<span class="badge badge-warning">Pending</span>';

                    return `
                        <tr>
                            <td>#${entry.id ?? '—'}</td>
                            <td>${entry.clockin ?? '—'}</td>
                            <td>${entry.clockout ?? '—'}</td>
                            <td>${Number(entry.hours || 0).toFixed(2)} hrs</td>
                            <td>₱${Number(entry.amount || 0).toFixed(2)}</td>
                            <td>${status}</td>
                        </tr>
                    `;
                }).join('');
                const assignmentRows = assignments.map((assignment) => `
                        <tr>
                            <td>
                                ${assignment.title || '—'}
                                ${assignment.code ? `<div class="muted">${assignment.code}</div>` : ''}
                                ${assignment.recurrence ? `<div class="muted">Recurring: ${assignment.recurrence}</div>` : ''}
                            </td>
                            <td>${assignment.date || '—'}</td>
                            <td>${assignment.time || '—'}</td>
                            <td>
                                ${Array.isArray(assignment.attendance) && assignment.attendance.length
                                    ? assignment.attendance.map((slot) => `<div>${slot}</div>`).join('')
                                    : '<span class="muted">No attendance</span>'}
                            </td>
                            <td>${Number(assignment.hours || 0).toFixed(2)} hrs</td>
                            <td>₱${Number(assignment.salary || 0).toFixed(2)}</td>
                        </tr>
                    `).join('');
                const infoFields = [
                    `<div><strong>${isTrainer ? 'Trainer' : 'Employee'}:</strong> ${data.name || '—'}</div>`,
                    `<div><strong>Email:</strong> ${data.email || '—'}</div>`,
                    `<div><strong>Period:</strong> ${data.month || '—'}</div>`,
                ];
                if (!isTrainer) {
                    infoFields.push(`<div><strong>Hourly rate:</strong> ₱${Number(data.rate || 0).toFixed(2)}</div>`);
                }

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
                                        <th>Pay</th>
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
                                    <strong>Summary</strong>
                                    <table class="totals">
                                        <tbody>
                                            <tr><td>Gross pay</td><td>₱${Number(data.gross || 0).toFixed(2)}</td></tr>
                                            <tr><td>SSS</td><td>₱${Number(data.deductions?.sss || 0).toFixed(2)}</td></tr>
                                            <tr><td>PhilHealth</td><td>₱${Number(data.deductions?.philhealth || 0).toFixed(2)}</td></tr>
                                            <tr><td>Pag-IBIG</td><td>₱${Number(data.deductions?.pagibig || 0).toFixed(2)}</td></tr>
                                            <tr><td>3kfitness app cut</td><td>₱${Number(data.deductions?.app_cut || 0).toFixed(2)}</td></tr>
                                            <tr><th>Net pay</th><th>₱${Number(data.net || 0).toFixed(2)}</th></tr>
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
            const appCutRate = Number(appCutInput?.value || 0) / 100;

            document.querySelectorAll('[data-payroll-card], [data-trainer-card]').forEach((card) => {
                const gross = Number(card.dataset.gross || 0);

                const sss = +(gross * sssRate).toFixed(2);
                const philhealth = +(gross * philRate).toFixed(2);
                const pagibigBase = pagibigCap > 0 ? Math.min(gross, pagibigCap) : gross;
                const pagibig = +(pagibigBase * pagibigRate).toFixed(2);
                const appCut = +(gross * appCutRate).toFixed(2);
                const net = Math.max(gross - (sss + philhealth + pagibig + appCut), 0);

                card.querySelectorAll('[data-sss]').forEach((el) => el.textContent = formatPeso(sss));
                card.querySelectorAll('[data-philhealth]').forEach((el) => el.textContent = formatPeso(philhealth));
                card.querySelectorAll('[data-pagibig]').forEach((el) => el.textContent = formatPeso(pagibig));
                card.querySelectorAll('[data-appcut]').forEach((el) => el.textContent = formatPeso(appCut));
                card.querySelectorAll('[data-net]').forEach((el) => el.textContent = formatPeso(net));

                const payslipBtn = card.querySelector('.payslip-btn');
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
            });
        }

        applyBtn?.addEventListener('click', () => {
            applyDeductions();
            const modalEl = document.getElementById('deductionModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal?.hide();
        });

        // Process preview modal (trainer processing)
        const processModalEl = document.getElementById('processPreviewModal');
        const processRangeSelect = document.getElementById('process-range-select');
        const processRangeList = document.getElementById('process-range-list');
        const processRangeSummary = document.getElementById('process-range-summary');
        const processRangeTotal = document.getElementById('process-range-total');
        const processRangeCount = document.getElementById('process-range-count');
        const processDayFilter = document.getElementById('process-day-filter');
        const processConfirmBtn = document.getElementById('process-range-confirm');
        let pendingProcessForm = null;
        let pendingAssignments = [];

        // Ensure the preview modal lives under body to avoid overflow clipping
        if (processModalEl && processModalEl.parentElement !== document.body) {
            document.body.appendChild(processModalEl);
        }

        function renderProcessAssignments() {
            if (!processRangeList) return;
            processRangeList.innerHTML = '';
            const todayDayForProcessing = todayDay;
            const selectedRange = processingRanges.find((range) => {
                const from = parseInt(range.from, 10);
                const to = parseInt(range.to ?? 31, 10);
                return Number.isInteger(from) && Number.isInteger(to) && todayDayForProcessing >= from && todayDayForProcessing <= to;
            });

            const filtered = pendingAssignments.filter((item) => {
                if (!selectedRange) return true;
                const days = Array.isArray(item.timeline) ? item.timeline.map((t) => parseInt(t.day, 10)).filter((d) => Number.isInteger(d)) : [];
                if (!days.length) return false;
                return days.some((d) => d >= (selectedRange.from || 0) && d <= (selectedRange.to || 31));
            });

            if (processRangeSummary) {
                if (selectedRange) {
                    processRangeSummary.textContent = `Today (day ${todayDayForProcessing}) in range ${selectedRange.from}-${selectedRange.to} → process on day ${selectedRange.process}`;
                } else {
                    processRangeSummary.textContent = `Today (day ${todayDayForProcessing}) not in any range; no sessions to show`;
                }
            }
            if (!selectedRange || !filtered.length) {
                const empty = document.createElement('div');
                empty.className = 'text-center text-muted';
                empty.textContent = 'No assignments match today’s processing range.';
                processRangeList.appendChild(empty);
                if (processRangeTotal) processRangeTotal.textContent = '₱0.00 total';
                if (processRangeCount) processRangeCount.textContent = '0 assignments';
                return;
            }

            let totalAmount = 0;

            filtered.forEach((item) => {
                const timeline = Array.isArray(item.timeline) ? item.timeline : [];
                const filteredTimeline = selectedRange
                    ? timeline.filter((t) => {
                        const day = parseInt(t.day, 10);
                        if (!Number.isInteger(day)) return false;
                        return day >= (selectedRange.from || 0) && day <= (selectedRange.to || 31);
                    })
                    : timeline;

                const dates = filteredTimeline.map((t) => t.label || '').filter(Boolean).join(', ');
                const totalCompleted = timeline.filter((t) => (t.status || '').toLowerCase().includes('completed')).length;
                const filteredCompleted = filteredTimeline.filter((t) => (t.status || '').toLowerCase().includes('completed')).length;
                const completedAmount = Number(item.amounts?.completed || 0);
                const amountShare = totalCompleted > 0 ? (filteredCompleted / totalCompleted) : 0;
                const amount = Number(completedAmount * amountShare);
                totalAmount += amount;

                const timelineHtml = filteredTimeline.length
                    ? filteredTimeline.map((t) => {
                        const status = (t.status || '').toLowerCase();
                        let cls = 'bg-secondary';
                        if (status.includes('upcoming')) cls = 'bg-warning text-dark';
                        else if (status.includes('paid') || status.includes('completed')) cls = 'bg-success';
                        return `
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="process-dot" style="background:#3b82f6;"></span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small mb-0">${t.label || '—'}</div>
                                    <span class="badge ${cls} px-2 py-1">${t.status || ''}</span>
                                </div>
                            </div>
                        `;
                    }).join('')
                    : '<div class="text-muted small">No session dates listed.</div>';

                const card = document.createElement('div');
                card.className = 'process-card';
                card.innerHTML = `
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold">${item.title || 'Unnamed Schedule'}</div>
                            ${item.code ? `<div class="text-muted small">Code: ${item.code}</div>` : ''}
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success">₱${amount.toFixed(2)}</div>
                            <div class="text-muted small">
                                ${selectedRange ? `Processing on day ${selectedRange.process}` : 'Processing day not set for today'}
                            </div>
                        </div>
                    </div>
                    <div class="text-muted small mt-1">${dates || 'No dates in this range'}</div>
                    <div class="process-timeline mt-2">${timelineHtml}</div>
                `;
                processRangeList.appendChild(card);
            });

            if (processRangeTotal) {
                processRangeTotal.textContent = `₱${totalAmount.toFixed(2)} total`;
            }
            if (processRangeCount) {
                processRangeCount.textContent = `${filtered.length} ${filtered.length === 1 ? 'assignment' : 'assignments'}`;
            }
        }

        document.querySelectorAll('.process-payroll-btn').forEach((btn) => {
            if (!btn.dataset.rangeAssignments) return;
            btn.addEventListener('click', (e) => {
                const form = btn.closest('form');
                let data = [];
                try {
                    data = JSON.parse(btn.dataset.rangeAssignments || '[]');
                } catch (err) {
                    data = [];
                }

                if (!processModalEl) return;
                e.preventDefault();
                pendingProcessForm = form;
                pendingAssignments = Array.isArray(data) ? data : [];
                renderProcessAssignments();
                const modal = bootstrap.Modal.getOrCreateInstance(processModalEl);
                modal.show();
            });
        });

        processDayFilter?.addEventListener('change', renderProcessAssignments);
        processConfirmBtn?.addEventListener('click', () => {
            if (pendingProcessForm) {
                pendingProcessForm.submit();
            }
        });

        // Assignment modal filters
        document.querySelectorAll('.assignment-modal').forEach((modal) => {
            const cards = modal.querySelectorAll('[data-assignment-card]');
            const buttons = modal.querySelectorAll('[data-filter-button]');
            const resetBtn = modal.querySelector('[data-filter-reset]');
            const startInput = modal.querySelector('[data-filter-start]');
            const endInput = modal.querySelector('[data-filter-end]');
            const monthInput = modal.querySelector('[data-filter-month]');
            const rangeInput = modal.querySelector('[data-filter-range]');
            const processingRanges = Array.isArray(serverProcessingRanges) ? serverProcessingRanges : [];
            let activeFilter = 'all';
            const detailPanel = modal.querySelector('[data-assignment-detail]');
            let selectedCard = null;
            let selectedRange = null;

            function setActive(targetFilter) {
                activeFilter = targetFilter;
                buttons.forEach((btn) => {
                    btn.classList.toggle('active', btn.dataset.filter === targetFilter);
                });
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
                        <div class="small mb-0">Hours per session: ${Number(series.hours_per_occurrence || 0).toFixed(2)}</div>
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
                        ${attendance.length
                            ? `<ul class="list-unstyled small mb-0 mt-1">${attendance.map((a) => `<li>${a}</li>`).join('')}</ul>`
                            : '<p class="text-muted small mb-0">No attendance recorded.</p>'
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
                cards.forEach((c) => c.classList.remove('border-primary', 'shadow-sm'));
                card.classList.add('border-primary', 'shadow-sm');
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
                // If month is chosen, sync start/end to that month
                if (monthInput && monthInput.value) {
                    const [year, month] = monthInput.value.split('-').map((v) => parseInt(v, 10));
                    if (!Number.isNaN(year) && !Number.isNaN(month)) {
                        const startDate = new Date(year, month - 1, 1);
                        const endDate = new Date(year, month, 0);
                        if (startInput) startInput.valueAsDate = startDate;
                        if (endInput) endInput.valueAsDate = endDate;
                    }
                }

                const filterStart = parseDate(startInput?.value);
                const filterEnd = parseDate(endInput?.value);
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

                    let categoryMatch = true;
                    if (activeFilter === 'future') {
                        categoryMatch = hasFuture;
                    } else if (activeFilter === 'past') {
                        categoryMatch = hasPast;
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

                    const show = categoryMatch && dateMatch && rangeMatch;
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

            cards.forEach((card) => {
                card.addEventListener('click', () => selectCard(card));
            });

            // Default state
            applyFilters();
            const firstVisible = Array.from(cards).find((card) => !card.classList.contains('d-none'));
            selectCard(firstVisible || null);
        });
    });
</script>
@endsection
