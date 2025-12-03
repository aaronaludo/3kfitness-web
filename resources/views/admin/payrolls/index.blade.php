@extends('layouts.admin')
@section('title', 'Payrolls')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $searchTerm = request('member_name');
                $printSource = $data;
                $printPayrolls = collect($printSource->items())->map(function ($item) {
                    $clockIn = $item->clockin_at ? \Carbon\Carbon::parse($item->clockin_at) : null;
                    $clockOut = $item->clockout_at ? \Carbon\Carbon::parse($item->clockout_at) : null;
                    $hoursWorked = ($clockIn && $clockOut)
                        ? round($clockOut->diffInMinutes($clockIn) / 60, 2)
                        : null;
                    $rate = $item->user->rate_per_hour ?? null;
                    $totalAmount = (!is_null($hoursWorked) && !is_null($rate))
                        ? round($hoursWorked * $rate, 2)
                        : null;
                    $statusLabel = $clockOut ? 'Completed' : 'Pending clock-out';

                    return [
                        'id' => $item->id,
                        'staff' => trim(($item->user->first_name ?? '') . ' ' . ($item->user->last_name ?? '')) ?: '—',
                        'rate' => !is_null($rate) ? number_format((float) $rate, 2) : null,
                        'clock_in' => $clockIn ? $clockIn->format('M j, Y g:i A') : '—',
                        'clock_out' => $clockOut ? $clockOut->format('M j, Y g:i A') : '—',
                        'hours' => !is_null($hoursWorked) ? number_format($hoursWorked, 2) : null,
                        'amount' => !is_null($totalAmount) ? number_format($totalAmount, 2) : null,
                        'status' => $statusLabel,
                        'created_at' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('M j, Y g:i A') : '',
                        'updated_at' => $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->format('M j, Y g:i A') : '',
                    ];
                })->values();

                $printPayload = [
                    'title' => 'Payroll activity',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => $searchTerm,
                    ],
                    'count' => $printPayrolls->count(),
                    'items' => $printPayrolls,
                ];
            @endphp
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 mt-2">
                <div>
                    <h2 class="title mb-0">Payrolls</h2>
                </div>
                <div class="d-flex align-items-center">
                    <a
                        href="{{ route('admin.payrolls.process') }}"
                        class="btn btn-primary d-flex align-items-center gap-2 me-2"
                    >
                        <i class="fa-solid fa-gears"></i>
                        Process payroll
                    </a>
                    <button
                        type="button"
                        class="btn btn-danger d-flex align-items-center gap-2"
                        id="print-submit-button"
                        data-print='@json($printPayload)'
                        aria-label="Open printable/PDF view of payroll results"
                    >
                        <i class="fa-solid fa-print"></i>
                        <span id="print-loader" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Print
                    </button>
                </div>
            </div>

            @php
                $pageCollection = collect($data->items());
                $pageCompleted = $pageCollection->filter(fn ($item) => $item->clockout_at)->count();
                $pagePending = $pageCollection->filter(fn ($item) => !$item->clockout_at)->count();
                $pageTotalHours = $pageCollection->sum(function ($item) {
                    if (!$item->clockin_at || !$item->clockout_at) {
                        return 0;
                    }

                    $clockIn = \Carbon\Carbon::parse($item->clockin_at);
                    $clockOut = \Carbon\Carbon::parse($item->clockout_at);

                    return round($clockOut->diffInMinutes($clockIn) / 60, 2);
                });
                $pageTotalAmount = $pageCollection->sum(function ($item) {
                    if (!$item->clockin_at || !$item->clockout_at) {
                        return 0;
                    }

                    $clockIn = \Carbon\Carbon::parse($item->clockin_at);
                    $clockOut = \Carbon\Carbon::parse($item->clockout_at);
                    $hours = $clockOut->diffInMinutes($clockIn) / 60;
                    $rate = $item->user->rate_per_hour ?? 0;

                    return round($hours * $rate, 2);
                });
            @endphp

            <div class="col-12 mb-20">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Overview</span>
                                <h4 class="fw-semibold mb-1">Payroll activity</h4>
                                <p class="text-muted mb-0">Track staff clock-ins, verify completed timesheets, and review payouts in one place.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">Showing {{ $data->total() }} results</span>
                                <span class="d-block text-muted small">Page {{ $data->currentPage() }} of {{ $data->lastPage() }}</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.payrolls.index') }}" method="GET" id="payroll-filter-form" class="mt-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 240px;">
                                    <div class="position-relative">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>
                                        <input
                                            type="search"
                                            class="form-control rounded-pill ps-5"
                                            name="member_name"
                                            placeholder="Search staff name"
                                            value="{{ $searchTerm }}"
                                            aria-label="Search staff name"
                                        />
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if($searchTerm)
                                        <a href="{{ route('admin.payrolls.index') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                                    @endif
                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <div class="tile tile-primary h-100">
                            <div class="tile-heading">Completed entries (page)</div>
                            <div class="tile-body">
                                <i class="fa-solid fa-circle-check"></i>
                                <h2 class="float-end mb-0">{{ $pageCompleted }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="tile tile-primary h-100">
                            <div class="tile-heading">Pending clock-outs (page)</div>
                            <div class="tile-body">
                                <i class="fa-solid fa-hourglass-half"></i>
                                <h2 class="float-end mb-0">{{ $pagePending }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="tile tile-primary h-100">
                            <div class="tile-heading">Total payout (page)</div>
                            <div class="tile-body">
                                <i class="fa-solid fa-peso-sign"></i>
                                <h2 class="float-end mb-0">₱{{ number_format($pageTotalAmount, 2) }}</h2>
                            </div>
                        </div>
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
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Staff</th>
                                        <th scope="col">Clock in</th>
                                        <th scope="col">Clock out</th>
                                        <th scope="col">Total hours</th>
                                        <th scope="col">Total amount</th>
                                        <th scope="col">Created</th>
                                        <th scope="col" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data as $item)
                                        @php
                                            $clockIn = $item->clockin_at ? \Carbon\Carbon::parse($item->clockin_at) : null;
                                            $clockOut = $item->clockout_at ? \Carbon\Carbon::parse($item->clockout_at) : null;
                                            $hoursWorked = ($clockIn && $clockOut)
                                                ? round($clockOut->diffInMinutes($clockIn) / 60, 2)
                                                : null;
                                            $totalAmount = $hoursWorked ? $hoursWorked * ($item->user->rate_per_hour ?? 0) : null;
                                        @endphp
                                        <tr>
                                            <td class="text-muted">#{{ $item->id }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $item->user->first_name }} {{ $item->user->last_name }}</div>
                                                @if(!is_null($item->user->rate_per_hour))
                                                    <span class="text-muted small">₱{{ number_format($item->user->rate_per_hour, 2) }} / hour</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($clockIn)
                                                    <span class="fw-semibold d-block">{{ $clockIn->format('M d, Y') }}</span>
                                                    <span class="text-muted small">{{ $clockIn->format('g:i A') }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($clockOut)
                                                    <span class="fw-semibold d-block">{{ $clockOut->format('M d, Y') }}</span>
                                                    <span class="text-muted small">{{ $clockOut->format('g:i A') }}</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning fw-semibold rounded-pill px-3 py-1">Awaiting</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(!is_null($hoursWorked))
                                                    <span class="fw-semibold d-block">{{ number_format($hoursWorked, 2) }} hrs</span>
                                                    <span class="badge bg-success-subtle text-success fw-semibold rounded-pill px-3 py-1">Complete</span>
                                                @else
                                                    <span class="text-muted">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(!is_null($totalAmount))
                                                    <span class="fw-semibold d-block">₱{{ number_format($totalAmount, 2) }}</span>
                                                    <span class="text-muted small">Calculated from hours worked</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-semibold d-block">{{ $item->created_at?->format('M d, Y') }}</span>
                                                <span class="text-muted small">{{ $item->created_at?->format('g:i A') }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center">
                                                    <div class="action-button">
                                                        <a href="{{ route('admin.payrolls.view', $item->id) }}" title="View">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                No payroll entries found. Adjust your filters or check back later.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            {{ $data->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printButton = document.getElementById('print-submit-button');
            const printLoader = document.getElementById('print-loader');

            function getStatusBadgeClass(status) {
                if (!status) return 'badge-soft-muted';
                const normalized = status.toLowerCase();
                if (normalized.includes('pending')) return 'badge-soft-warning';
                if (normalized.includes('complete')) return 'badge-soft-success';
                return 'badge-soft-secondary';
            }

            function buildFilters(filters) {
                const chips = [];
                if (filters.search) {
                    chips.push(`Search: ${filters.search}`);
                }
                return chips.map((chip) => `<span class="pill">${chip}</span>`).join('') || '<span class="muted">No filters applied</span>';
            }

            function buildRows(items) {
                return items.map((item) => `
                    <tr>
                        <td>${item.id ?? '—'}</td>
                        <td>
                            <div class="fw">${item.staff || '—'}</div>
                            <div class="muted">${item.rate ? '₱' + item.rate + ' / hr' : ''}</div>
                        </td>
                        <td>${item.clock_in || '—'}</td>
                        <td>${item.clock_out || '—'}</td>
                        <td>${item.hours ? item.hours + ' hrs' : '—'}</td>
                        <td>${item.amount ? '₱' + item.amount : '—'}</td>
                        <td><span class="badge ${getStatusBadgeClass(item.status)}">${item.status || '—'}</span></td>
                        <td>
                            <div>${item.created_at || ''}</div>
                            <div class="muted">${item.updated_at || ''}</div>
                        </td>
                    </tr>
                `).join('');
            }

            function renderPrintWindow(payload) {
                const items = payload.items || [];
                const filters = payload.filters || {};
                const rows = buildRows(items);
                const html = `
                    <!doctype html>
                    <html>
                        <head>
                            <title>${payload.title || 'Payroll activity'}</title>
                            <style>
                                :root { color-scheme: light; }
                                body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 24px; color: #111827; }
                                .sheet { max-width: 1100px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px 28px; }
                                .header { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
                                .title { margin: 0; font-size: 22px; }
                                .muted { color: #6b7280; font-size: 12px; }
                                .pill-row { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; }
                                .pill { background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 999px; padding: 6px 12px; font-size: 12px; }
                                table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 13px; }
                                th, td { border: 1px solid #e5e7eb; padding: 10px; vertical-align: top; }
                                th { background: #f9fafb; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
                                .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
                                .badge-soft-warning { background: #fef9c3; color: #854d0e; }
                                .badge-soft-success { background: #dcfce7; color: #166534; }
                                .badge-soft-secondary { background: #e5e7eb; color: #374151; }
                                .badge-soft-muted { background: #f3f4f6; color: #6b7280; }
                                .fw { font-weight: 700; }
                            </style>
                        </head>
                        <body>
                            <div class="sheet">
                                <div class="header">
                                    <div>
                                        <h1 class="title">${payload.title || 'Payroll activity'}</h1>
                                        <div class="muted">Generated ${payload.generated_at || ''}</div>
                                        <div class="muted">Showing ${payload.count || 0} record(s)</div>
                                    </div>
                                </div>
                                <div class="pill-row">${buildFilters(filters)}</div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Staff</th>
                                            <th>Clock-in</th>
                                            <th>Clock-out</th>
                                            <th>Hours</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Audit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows || '<tr><td colspan="8" style="text-align:center; padding:16px;">No payroll records for this view.</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                            <script>window.print();<\/script>
                        </body>
                    </html>
                `;

                const printWindow = window.open('', '_blank', 'width=1200,height=900');
                if (!printWindow) return false;
                printWindow.document.open();
                printWindow.document.write(html);
                printWindow.document.close();
                return true;
            }

            if (printButton) {
                printButton.addEventListener('click', function (e) {
                    const rawPayload = printButton.dataset.print;
                    if (!rawPayload) {
                        return;
                    }

                    e.preventDefault();
                    if (printLoader) printLoader.classList.remove('d-none');
                    printButton.disabled = true;

                    let payload = null;
                    try {
                        payload = JSON.parse(rawPayload);
                    } catch (err) {
                        payload = null;
                    }

                    const opened = payload ? renderPrintWindow(payload) : false;
                    if (!opened) {
                        printButton.disabled = false;
                        if (printLoader) printLoader.classList.add('d-none');
                        window.print();
                        return;
                    }

                    setTimeout(() => {
                        printButton.disabled = false;
                        if (printLoader) printLoader.classList.add('d-none');
                    }, 300);
                });
            }
        });
    </script>
@endsection
