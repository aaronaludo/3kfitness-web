@extends('layouts.admin')
@section('title', 'Payroll History')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $searchTerm = request('member_name');
                $periodMonth = request('period_month');
                $processedFrom = request('processed_from');
                $processedTo = request('processed_to');
                $roleFilter = $roleFilter ?? request('role', 'all');
                $generatedByUser = auth()->guard('admin')->user();
                $generatedByName = $generatedByUser
                    ? trim(($generatedByUser->first_name ?? '') . ' ' . ($generatedByUser->last_name ?? ''))
                    : '';
                if ($generatedByName === '') {
                    $generatedByName = optional($generatedByUser)->name ?? '—';
                }
                $advancedFiltersOpen = request()->filled('processed_from') || request()->filled('processed_to');
                $printSource = $runs;
                $printAllSource = $printAllRuns ?? collect();
                $currencySymbol = '₱';
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
                $calculateTotals = function ($source) {
                    $collection = $source instanceof \Illuminate\Pagination\AbstractPaginator
                        ? collect($source->items())
                        : collect($source ?? []);

                    $sum = fn ($key) => $collection->sum(function ($run) use ($key) {
                        return (float) (is_array($run) ? ($run[$key] ?? 0) : ($run->$key ?? 0));
                    });

                    return [
                        'hours' => round($sum('total_hours'), 2),
                        'gross' => round($sum('gross_pay'), 2),
                        'sss' => round($sum('deduction_sss'), 2),
                        'philhealth' => round($sum('deduction_philhealth'), 2),
                        'pagibig' => round($sum('deduction_pagibig'), 2),
                        'app_cut' => round($sum('deduction_app_cut'), 2),
                        'net' => round($sum('net_pay'), 2),
                    ];
                };
                $pageTotals = $calculateTotals($printSource);
                $allTotals = $calculateTotals($printAllSource);
                $mapRun = function ($run) {
                    $staff = $run->user;
                    $name = $staff ? trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) : 'Unknown';
                    $email = optional($staff)->email ?? '—';
                    $userCode = optional($staff)->user_code;
                    $periodLabel = $run->period_month ?? '—';
                    $processedAt = $run->processed_at
                        ? $run->processed_at->format('M d, Y g:i A')
                        : ($run->created_at?->format('M d, Y g:i A') ?? '—');
                    $releasedAt = $run->released_at
                        ? $run->released_at->format('M d, Y g:i A')
                        : '—';
                    $releaseStatus = $run->released_at ? 'Released' : 'Pending';
                    $processedSessions = collect($run->processed_session_series ?? []);
                    $processedSessionCount = $processedSessions->sum(function ($item) {
                        return collect($item['sessions'] ?? [])->count();
                    });

                    return [
                        'id' => $run->id,
                        'name' => $name !== '' ? $name : '—',
                        'email' => $email,
                        'user_code' => $userCode,
                        'period' => $periodLabel,
                        'hours' => (float) ($run->total_hours ?? 0),
                        'gross' => number_format((float) ($run->gross_pay ?? 0), 2),
                        'sss' => number_format((float) ($run->deduction_sss ?? 0), 2),
                        'philhealth' => number_format((float) ($run->deduction_philhealth ?? 0), 2),
                        'pagibig' => number_format((float) ($run->deduction_pagibig ?? 0), 2),
                        'app_cut' => number_format((float) ($run->deduction_app_cut ?? 0), 2),
                        'net' => number_format((float) ($run->net_pay ?? 0), 2),
                        'status' => $releaseStatus,
                        'processed_at' => $processedAt,
                        'released_at' => $releasedAt,
                        'processed_sessions' => $processedSessionCount,
                    ];
                };

                $printRuns = collect($printSource->items() ?? [])->map($mapRun)->values();
                $printAllRuns = collect($printAllSource ?? [])->map($mapRun)->values();

                $printPayload = [
                    'title' => 'Payroll history',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'member_name' => $searchTerm,
                        'period_month' => $periodMonth,
                        'processed_from' => $processedFrom,
                        'processed_to' => $processedTo,
                        'role' => $roleFilter,
                    ],
                    'currency_symbol' => $currencySymbol,
                    'totals' => $pageTotals,
                    'count' => $printRuns->count(),
                    'items' => $printRuns,
                ];

                $printAllPayload = [
                    'title' => 'Payroll history (all pages)',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'member_name' => $searchTerm,
                        'period_month' => $periodMonth,
                        'processed_from' => $processedFrom,
                        'processed_to' => $processedTo,
                        'role' => $roleFilter,
                        'scope' => 'all',
                    ],
                    'currency_symbol' => $currencySymbol,
                    'totals' => $allTotals,
                    'count' => $printAllRuns->count(),
                    'items' => $printAllRuns,
                ];
            @endphp

            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
                <div>
                    <h2 class="title mb-0">Payroll History</h2>
                    <p class="text-muted mb-0">Review processed payroll runs for staff.</p>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <a
                        href="{{ route('admin.payrolls.process') }}"
                        class="btn btn-outline-danger d-flex align-items-center gap-2"
                    >
                        <i class="fa-solid fa-gears"></i>
                        Process payroll
                    </a>
                    <form action="#" method="POST" id="print-form">
                        @csrf
                        <input type="hidden" name="member_name" value="{{ $searchTerm }}">
                        <input type="hidden" name="period_month" value="{{ $periodMonth }}">
                        <input type="hidden" name="processed_from" value="{{ $processedFrom }}">
                        <input type="hidden" name="processed_to" value="{{ $processedTo }}">
                        <input type="hidden" name="role" value="{{ $roleFilter }}">
                        <button
                            type="submit"
                            class="btn btn-danger d-flex align-items-center gap-2"
                            id="print-submit-button"
                            data-print='@json($printPayload)'
                            data-print-all='@json($printAllPayload)'
                            aria-label="Open printable/PDF view of filtered payrolls"
                        >
                            <i class="fa-solid fa-print"></i>
                            <span id="print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-12 mb-20">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Filters</span>
                                <h4 class="fw-semibold mb-1">Find a payroll run</h4>
                                <p class="text-muted mb-0">Search across staff, user code, period month, or processed date window to locate runs.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">Showing {{ $runs->total() }} results</span>
                                <span class="d-block text-muted small">Page {{ $runs->currentPage() }} of {{ $runs->lastPage() }}</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.payrolls.index') }}" method="GET" class="mt-4" id="payroll-filter-form">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-3 flex-grow-1">
                                    <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 260px;">
                                        <label class="form-label text-muted small mb-1" for="member_name">Search</label>
                                        <div class="position-relative">
                                            <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </span>
                                            <input
                                                type="search"
                                                class="form-control rounded-pill ps-5"
                                                name="member_name"
                                                id="member_name"
                                                placeholder="Search #, staff, code, period, date"
                                                value="{{ $searchTerm }}"
                                                aria-label="Search payroll runs"
                                            />
                                        </div>
                                    </div>

                                    <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 220px;">
                                        <label class="form-label text-muted small mb-1" for="period_month">Period month</label>
                                        <input
                                            type="month"
                                            class="form-control rounded-pill"
                                            name="period_month"
                                            id="period_month"
                                            value="{{ $periodMonth }}"
                                            aria-label="Filter by payroll month"
                                        />
                                    </div>

                                    <div class="flex-grow-1 flex-lg-grow-0">
                                        <label class="form-label text-muted small mb-1 d-block">Quick filter</label>
                                        <div class="btn-group" role="group" aria-label="Quick filter trainer and staff">
                                            <input
                                                type="radio"
                                                class="btn-check"
                                                name="role"
                                                id="payroll_role_staff"
                                                value="staff"
                                                {{ $roleFilter === 'staff' ? 'checked' : '' }}
                                            >
                                            <label class="btn btn-outline-dark" for="payroll_role_staff">Staff payroll</label>

                                            <input
                                                type="radio"
                                                class="btn-check"
                                                name="role"
                                                id="payroll_role_trainer"
                                                value="trainer"
                                                {{ $roleFilter === 'trainer' ? 'checked' : '' }}
                                            >
                                            <label class="btn btn-outline-dark" for="payroll_role_trainer">Trainer payroll</label>

                                            <input
                                                type="radio"
                                                class="btn-check"
                                                name="role"
                                                id="payroll_role_all"
                                                value="all"
                                                {{ $roleFilter === 'all' ? 'checked' : '' }}
                                            >
                                            <label class="btn btn-outline-dark" for="payroll_role_all">Show both</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <a href="{{ route('admin.payrolls.index') }}" class="btn btn-link text-decoration-none text-muted px-0">
                                        Reset
                                    </a>

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#payrollFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i>
                                        Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="payrollFiltersModal" tabindex="-1" aria-labelledby="payrollFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="payrollFiltersModalLabel">Advanced filters</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex flex-column gap-4">
                                                <div>
                                                    <span class="form-label text-muted text-uppercase small d-block mb-2">Processed date</span>
                                                    <div class="row g-2">
                                                        <div class="col-12 col-sm-6">
                                                            <label class="form-label small text-muted mb-1" for="processed_from">From</label>
                                                            <input
                                                                type="date"
                                                                class="form-control rounded-3"
                                                                name="processed_from"
                                                                id="processed_from"
                                                                value="{{ $processedFrom }}"
                                                                aria-label="Filter payrolls processed from date"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label class="form-label small text-muted mb-1" for="processed_to">To</label>
                                                            <input
                                                                type="date"
                                                                class="form-control rounded-3"
                                                                name="processed_to"
                                                                id="processed_to"
                                                                value="{{ $processedTo }}"
                                                                aria-label="Filter payrolls processed to date"
                                                            />
                                                        </div>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">Matches processed date, falls back to created date if missing.</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 pt-0">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fa-solid fa-magnifying-glass me-2"></i>Apply filters
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <div class="table-responsive mb-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Staff</th>
                                        <th scope="col">User Code</th>
                                        <th scope="col">Period</th>
                                        <th scope="col">Hours</th>
                                        <th scope="col">Gross</th>
                                        <th scope="col">SSS</th>
                                        <th scope="col">PhilHealth</th>
                                        <th scope="col">Pag-IBIG</th>
                                        <th scope="col">App cut</th>
                                        <th scope="col">Net</th>
                                        <th scope="col">Processed</th>
                                        <th scope="col">Release status</th>
                                        <th scope="col">Release Date</th>
                                        <th scope="col" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($runs as $run)
                                        @php
                                            $staff = $run->user;
                                            $name = $staff ? trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) : 'Unknown';
                                            $periodLabel = $run->period_month ?? '—';
                                            $processedAt = $run->processed_at
                                                ? $run->processed_at->format('M d, Y g:i A')
                                                : ($run->created_at?->format('M d, Y g:i A') ?? '—');
                                            $releasedAt = $run->released_at
                                                ? $run->released_at->format('M d, Y g:i A')
                                                : null;
                                            $releaseStatus = $run->released_at ? 'Released' : 'Pending';
                                            $releaseBadge = $run->released_at ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';
                                            $rate = ($run->total_hours ?? 0) > 0
                                                ? round((float) $run->gross_pay / max((float) $run->total_hours, 0.01), 2)
                                                : null;
                                            $isTrainer = optional($staff)->role_id === 5;
                                            $employmentTypeKey = optional($staff)->employment_type;
                                            $employmentTypeLabel = null;
                                            if ($employmentTypeKey !== null && $employmentTypeKey !== '') {
                                                $employmentTypeLabel = match ($employmentTypeKey) {
                                                    'salaried' => 'Salaried (Basic Pay)',
                                                    'contractor' => 'Contractor / Freelance',
                                                    default => $employmentTypeKey,
                                                };
                                            }
                                            $payslipDetail = $payslipDetails[$run->id] ?? ['entries' => [], 'assignments' => []];
                                            $processedSeries = collect($run->processed_session_series ?? []);
                                            $processedSessionCount = $processedSeries->sum(function ($item) {
                                                return collect($item['sessions'] ?? [])->count();
                                            });
                                            $payslipData = [
                                                'type' => $isTrainer ? 'trainer' : 'staff',
                                                'name' => $name,
                                                'email' => optional($staff)->email ?? '—',
                                                'month' => $periodLabel,
                                                'employment_type' => $employmentTypeLabel,
                                                'generated_by' => $generatedByName,
                                                'gross' => (float) ($run->gross_pay ?? 0),
                                                'net' => (float) ($run->net_pay ?? 0),
                                                'rate' => $rate,
                                                'hours' => (float) ($run->total_hours ?? 0),
                                                'deductions' => [
                                                    'sss' => (float) ($run->deduction_sss ?? 0),
                                                    'philhealth' => (float) ($run->deduction_philhealth ?? 0),
                                                    'pagibig' => (float) ($run->deduction_pagibig ?? 0),
                                                    'app_cut' => (float) ($run->deduction_app_cut ?? 0),
                                                ],
                                                'entries' => $payslipDetail['entries'] ?? [],
                                                'membership_payments' => $payslipDetail['membership_payments'] ?? ['count' => 0, 'total' => 0, 'currency' => 'PHP', 'items' => []],
                                                'assignments' => $payslipDetail['assignments'] ?? [],
                                            ];
                                        @endphp
                                        <tr>
                                            <td>{{ $run->id }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $name }}</div>
                                                <span class="text-muted small">{{ optional($staff)->email ?? '—' }}</span>
                                            </td>
                                            <td><span class="text-muted small">{{ optional($staff)->user_code ?? '—' }}</span></td>
                                            <td>{{ $periodLabel }}</td>
                                            <td><span class="fw-semibold">{{ $formatHours($run->total_hours ?? 0) }}</span></td>
                                            <td>₱{{ number_format((float) ($run->gross_pay ?? 0), 2) }}</td>
                                            <td>₱{{ number_format((float) ($run->deduction_sss ?? 0), 2) }}</td>
                                            <td>₱{{ number_format((float) ($run->deduction_philhealth ?? 0), 2) }}</td>
                                            <td>₱{{ number_format((float) ($run->deduction_pagibig ?? 0), 2) }}</td>
                                            <td>₱{{ number_format((float) ($run->deduction_app_cut ?? 0), 2) }}</td>
                                            <td class="text-success fw-semibold">₱{{ number_format((float) ($run->net_pay ?? 0), 2) }}</td>
                                            <td>{{ $processedAt }}</td>
                                            <td>
                                                <span class="badge {{ $releaseBadge }} rounded-pill px-3 py-2">{{ $releaseStatus }}</span>
                                            </td>
                                            <td>{{ $releasedAt ? $releasedAt : '—' }}</td>
                                            <td class="text-center">
                                                @if($staff)
                                                    @if($releasedAt)
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-secondary btn-sm payslip-btn"
                                                            data-payslip='@json($payslipData)'
                                                        >
                                                            <i class="fa-solid fa-file-pdf me-1"></i>
                                                            Print payslip
                                                        </button>
                                                    @else
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-secondary btn-sm release-cash-btn"
                                                            data-release-action="{{ route('admin.payrolls.release', $run->id) }}"
                                                            data-release-name="{{ $name }}"
                                                            data-release-code="{{ optional($staff)->user_code ?? '—' }}"
                                                        >
                                                            <i class="fa-solid fa-hand-holding-dollar me-1"></i>
                                                            Release cash
                                                        </button>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="15" class="text-center text-muted py-4">
                                                No payroll runs found. Adjust your filters or check back later.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="text-muted small">
                                Showing {{ $runs->firstItem() ?? 0 }} to {{ $runs->lastItem() ?? 0 }} of {{ $runs->total() }} results
                            </div>
                            <div class="ms-auto">
                                {{ $runs->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="releaseCashModal" tabindex="-1" aria-labelledby="releaseCashModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-sm">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="releaseCashModalLabel">Release cash</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="release-cash-form" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="mb-0">
                            Are you sure you want to release this payslip for
                            <strong data-release-name>—</strong>
                            <span class="text-muted">(Code: <span data-release-code>—</span>)</span>?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" data-release-submit>Confirm release</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="processedSeriesModal" tabindex="-1" aria-labelledby="processedSeriesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow-sm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-semibold mb-0" id="processedSeriesModalLabel">Processed sessions</h5>
                        <span class="text-muted small" id="processedSeriesSubtitle"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="processed-series-body" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printButton = document.getElementById('print-submit-button');
            const printForm = document.getElementById('print-form');
            const printLoader = document.getElementById('print-loader');
            const payrollFilterForm = document.getElementById('payroll-filter-form');
            const roleQuickFilters = document.querySelectorAll('input[name="role"]');
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
                    parts.push(`${wholeHours} ${wholeHours === 1 ? 'hr' : 'hrs'}`);
                }
                if (minutes > 0) {
                    parts.push(`${minutes} ${minutes === 1 ? 'min' : 'mins'}`);
                }
                return parts.join(' ');
            };
            function buildFilters(filters) {
                const formatDate = (value) => {
                    if (!value) return null;
                    const parsed = new Date(value);
                    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString();
                };
                const chips = [];
                if (filters.member_name) {
                    chips.push({
                        label: 'Search',
                        value: filters.member_name,
                    });
                }
                if (filters.period_month) chips.push({ label: 'Period', value: filters.period_month });
                if (filters.processed_from || filters.processed_to) {
                    const from = formatDate(filters.processed_from) || 'Any';
                    const to = formatDate(filters.processed_to) || 'Any';
                    chips.push({ label: 'Processed', value: `${from} → ${to}` });
                }
                if (filters.role && filters.role !== 'all') {
                    chips.push({ label: 'Role', value: filters.role === 'trainer' ? 'Trainer only' : 'Staff only' });
                }
                return chips;
            }

            function buildTotalsRow(totals, currencySymbol) {
                if (!totals) return null;
                const fmtMoney = (value) => `${currencySymbol}${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                const fmtHours = (value) => formatHoursWithMinutes(value);
                return [
                    '',
                    '<strong>Totals</strong>',
                    '',
                    '',
                    fmtHours(totals.hours),
                    fmtMoney(totals.gross),
                    fmtMoney(totals.sss),
                    fmtMoney(totals.philhealth),
                    fmtMoney(totals.pagibig),
                    fmtMoney(totals.app_cut),
                    `<span class="text-success fw-semibold">${fmtMoney(totals.net)}</span>`,
                    '',
                    '',
                    '',
                ];
            }

            function buildTotalsChips(totals, currencySymbol) {
                if (!totals) return [];
                const fmt = (value, suffix = '') => `${suffix}${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                const fmtMoney = (value) => fmt(value, currencySymbol);
                const fmtHours = (value) => formatHoursWithMinutes(value);
                return [
                    { label: 'Total hours', value: fmtHours(totals.hours) },
                    { label: 'Total gross', value: fmtMoney(totals.gross) },
                    { label: 'Total SSS', value: fmtMoney(totals.sss) },
                    { label: 'Total PhilHealth', value: fmtMoney(totals.philhealth) },
                    { label: 'Total Pag-IBIG', value: fmtMoney(totals.pagibig) },
                    { label: 'Total app cut', value: fmtMoney(totals.app_cut) },
                    { label: 'Total net', value: fmtMoney(totals.net) },
                ];
            }

            function buildRows(items, totals, currencySymbol) {
                const rows = (items || []).map((item) => ([
                    item.id ?? '—',
                    `<div class="fw">${item.name || '—'}</div><div class="muted">${item.email || ''}</div>`,
                    item.user_code || '—',
                    item.period || '—',
                    formatHoursWithMinutes(item.hours),
                    `${currencySymbol}${item.gross || '0.00'}`,
                    `${currencySymbol}${item.sss || '0.00'}`,
                    `${currencySymbol}${item.philhealth || '0.00'}`,
                    `${currencySymbol}${item.pagibig || '0.00'}`,
                    `${currencySymbol}${item.app_cut || '0.00'}`,
                    `<span class="text-success fw-semibold">${currencySymbol}${item.net || '0.00'}</span>`,
                    item.processed_at || '—',
                    item.status || 'Pending',
                    item.released_at || '—',
                ]));

                const totalsRow = buildTotalsRow(totals, currencySymbol);
                if (totalsRow) {
                    rows.push(totalsRow);
                }
                return rows;
            }

            function renderPrintWindow(payload) {
                const rawItems = payload && payload.items ? payload.items : [];
                const items = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
                const filters = buildFilters(payload.filters || {});
                const currencySymbol = payload.currency_symbol || '₱';
                const headers = [
                    '#',
                    'Staff',
                    'User Code',
                    'Period',
                    'Hours',
                    'Gross',
                    'SSS',
                    'PhilHealth',
                    'Pag-IBIG',
                    'App cut',
                    'Net',
                    'Processed',
                    'Release status',
                    'Release Date'
                ];
                const rows = buildRows(items, payload.totals, currencySymbol);
                const totalsChips = buildTotalsChips(payload.totals, currencySymbol);
                const filterChips = filters.concat(totalsChips);

                return window.PrintPreview
                    ? PrintPreview.tryOpen(payload, headers, rows, filterChips)
                    : false;
            }

            if (printButton && printForm) {
                printButton.addEventListener('click', async function (e) {
                    const rawPayload = printButton.dataset.print;
                    const rawAllPayload = printButton.dataset.printAll;
                    if (!rawPayload) return;

                    e.preventDefault();
                    if (printLoader) printLoader.classList.remove('d-none');
                    printButton.disabled = true;

                    let payload = null;
                    let allPayload = null;
                    try {
                        payload = JSON.parse(rawPayload);
                    } catch (err) {
                        payload = null;
                    }
                    try {
                        allPayload = rawAllPayload ? JSON.parse(rawAllPayload) : null;
                    } catch (err) {
                        allPayload = null;
                    }

                    const scope = window.PrintPreview && PrintPreview.chooseScope
                        ? await PrintPreview.chooseScope()
                        : 'current';

                    if (!scope) {
                        printButton.disabled = false;
                        if (printLoader) printLoader.classList.add('d-none');
                        return;
                    }

                    const payloadToUse = scope === 'all' && allPayload ? allPayload : payload;
                    const handled = payloadToUse ? renderPrintWindow(payloadToUse) : false;

                    if (!handled) {
                        printButton.disabled = false;
                        if (printLoader) printLoader.classList.add('d-none');
                        return;
                    }

                    printButton.disabled = false;
                    if (printLoader) printLoader.classList.add('d-none');
                });
            }

            if (payrollFilterForm && roleQuickFilters.length) {
                roleQuickFilters.forEach((input) => {
                    input.addEventListener('change', () => {
                        if (typeof payrollFilterForm.requestSubmit === 'function') {
                            payrollFilterForm.requestSubmit();
                        } else {
                            payrollFilterForm.submit();
                        }
                    });
                });
            }

            // Payslip preview/print (matches process page)
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
                    const employmentType = data.employment_type ?? '';
                    const normalizeAmount = (value) => {
                        const num = Number(value);
                        return Number.isFinite(num) ? num : 0;
                    };
                    const isZeroAmount = (value) => Math.abs(normalizeAmount(value)) < 0.005;
                    const formatMoney = (value) => `₱${normalizeAmount(value).toFixed(2)}`;
                    const hourlyRate = formatMoney(data.rate || 0);
                    const totalHours = formatHoursWithMinutes(data.hours);
                    const generatedBy = data.generated_by || '—';
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
                                <td>${formatHoursWithMinutes(entry.hours)}</td>
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
                                <td>${formatHoursWithMinutes(assignment.hours)}</td>
                                <td>₱${Number(assignment.salary || 0).toFixed(2)}</td>
                            </tr>
                        `).join('');
                    const infoFields = [
                        `<div><strong>${isTrainer ? 'Trainer' : 'Employee'}:</strong> ${data.name || '—'}</div>`,
                        `<div><strong>Email:</strong> ${data.email || '—'}</div>`,
                        `<div><strong>Period:</strong> ${data.month || '—'}</div>`,
                        `<div><strong>Employment Type:</strong> ${employmentType}</div>`,
                        `<div><strong>Per hour rate:</strong> ${hourlyRate}</div>`,
                        `<div><strong>Total hours:</strong> ${totalHours}</div>`,
                        `<div><strong>Generated By:</strong> ${generatedBy}</div>`,
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
                        <div class="section">
                            <strong>Membership payments approved (period)</strong>
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Member</th>
                                        <th>Membership</th>
                                        <th>Amount</th>
                                        <th>Created Date</th>
                                        <th>Approved Date</th>
                                        <th>Expires Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${
                                        membershipPayments.length
                                            ? membershipPayments.map((pay) => `
                                                <tr>
                                                    <td>#${pay.id ?? '—'}</td>
                                                    <td>
                                                        ${pay.member_name || '—'}
                                                        <div class="muted">${pay.member_code ? `Code: ${pay.member_code}` : ''}</div>
                                                    </td>
                                                    <td>${pay.membership || '—'}</td>
                                                    <td>${pay.currency || 'PHP'} ${Number(pay.price || 0).toFixed(2)}</td>
                                                    <td>${pay.created_at || '—'}</td>
                                                    <td>${pay.updated_at || '—'}</td>
                                                    <td>${pay.expiration_at || '—'}</td>
                                                </tr>
                                            `).join('')
                                            : '<tr><td colspan="7" style="text-align:center;">No approved membership payments for this period.</td></tr>'
                                    }
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

            const releaseModalEl = document.getElementById('releaseCashModal');
            const releaseForm = document.getElementById('release-cash-form');
            const releaseName = releaseModalEl ? releaseModalEl.querySelector('[data-release-name]') : null;
            const releaseCode = releaseModalEl ? releaseModalEl.querySelector('[data-release-code]') : null;
            const releaseSubmit = document.querySelector('[data-release-submit]');

            if (releaseModalEl && releaseModalEl.parentElement !== document.body) {
                document.body.appendChild(releaseModalEl);
            }

            document.querySelectorAll('.release-cash-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (!releaseForm || !releaseModalEl) return;
                    releaseForm.action = btn.dataset.releaseAction || '';
                    if (releaseName) releaseName.textContent = btn.dataset.releaseName || '—';
                    if (releaseCode) releaseCode.textContent = btn.dataset.releaseCode || '—';
                    if (typeof bootstrap !== 'undefined') {
                        const modal = bootstrap.Modal.getOrCreateInstance(releaseModalEl);
                        modal.show();
                    }
                });
            });

            if (releaseForm && releaseSubmit) {
                releaseForm.addEventListener('submit', () => {
                    releaseSubmit.disabled = true;
                    releaseSubmit.textContent = 'Releasing...';
                });
            }

            const processedSeriesModal = document.getElementById('processedSeriesModal');
            const processedSeriesBody = document.getElementById('processed-series-body');
            const processedSeriesSubtitle = document.getElementById('processedSeriesSubtitle');
            const processedSeriesTitle = document.getElementById('processedSeriesModalLabel');

            function renderProcessedSeries(series, runName, period) {
                if (processedSeriesSubtitle) {
                    processedSeriesSubtitle.textContent = `${runName || '—'} • ${period || '—'}`;
                }
                if (processedSeriesBody) {
                    processedSeriesBody.innerHTML = '';
                    if (!series.length) {
                        const empty = document.createElement('div');
                        empty.className = 'text-muted text-center';
                        empty.textContent = 'No processed sessions recorded for this run.';
                        processedSeriesBody.appendChild(empty);
                        return;
                    }

                    series.forEach((item) => {
                        const sessions = Array.isArray(item.sessions) ? item.sessions : [];
                        const sessionList = sessions.length
                            ? sessions.map((session) => {
                                const badge = document.createElement('span');
                                badge.className = 'badge bg-success-subtle text-success border border-success-subtle';
                                badge.textContent = session.status || 'Completed';

                                const row = document.createElement('div');
                                row.className = 'd-flex align-items-center justify-content-between border rounded-3 px-3 py-2 mb-1';
                                row.innerHTML = `
                                    <div>
                                        <div class="fw-semibold">${session.label || session.date || '—'}</div>
                                        ${session.day ? `<div class="text-muted small">Day ${session.day}</div>` : ''}
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        ${badge.outerHTML}
                                    </div>
                                `;
                                return row.outerHTML;
                            }).join('')
                            : '<div class="text-muted small">No session dates saved.</div>';

                        const card = document.createElement('div');
                        card.className = 'border rounded-4 p-3';
                        card.innerHTML = `
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <div class="fw-semibold">${item.schedule_name || 'Class schedule'}</div>
                                    ${item.class_code ? `<div class="text-muted small">Code: ${item.class_code}</div>` : ''}
                                    ${item.time_range ? `<div class="text-muted small">Time: ${item.time_range}</div>` : ''}
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">₱${Number(item.payroll_salary || 0).toFixed(2)}</div>
                                    <div class="text-muted small">${formatHoursWithMinutes(item.payroll_hours)}</div>
                                </div>
                            </div>
                            <div class="mt-2">
                                ${sessionList}
                            </div>
                        `;
                        processedSeriesBody.appendChild(card);
                    });
                }

                if (processedSeriesModal && typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getOrCreateInstance(processedSeriesModal);
                    modal.show();
                }
            }

            document.querySelectorAll('.processed-series-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    let series = [];
                    try {
                        series = JSON.parse(btn.dataset.series || '[]');
                    } catch (e) {
                        series = [];
                    }
                    const runName = btn.dataset.runName || '';
                    const period = btn.dataset.period || '';
                    if (processedSeriesTitle) {
                        processedSeriesTitle.textContent = 'Processed sessions';
                    }
                    renderProcessedSeries(Array.isArray(series) ? series : [], runName, period);
                });
            });
        });
    </script>
@endsection
