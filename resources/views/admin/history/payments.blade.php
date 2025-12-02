@extends('layouts.admin')
@section('title', 'Payments History')

@section('content')
    <div class="container-fluid">
        @php
            $activeStatus = $filters['status'] ?? 'all';
            $statusLabels = [
                'all' => ['label' => 'All statuses', 'class' => 'bg-secondary'],
                'approved' => ['label' => 'Approved', 'class' => 'bg-success'],
                'pending' => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                'rejected' => ['label' => 'Rejected', 'class' => 'bg-danger'],
            ];
            $showArchived = $filters['show_archived'] ?? false;
            $hasFilters = ($filters['search'] ?? '') !== '' || ($filters['membership_id'] ?? null) || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) || $activeStatus !== 'all' || $showArchived;
            $advancedFiltersOpen = ($filters['membership_id'] ?? null) || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) || $activeStatus !== 'all';

            $printItems = collect($payments->items())->map(function ($payment) {
                $member = $payment->user;
                $membership = $payment->membership;
                $purchasedAt = $payment->created_at ? $payment->created_at->format('M d, Y g:i A') : null;
                $expiresAt = $payment->expiration_at ? \Carbon\Carbon::parse($payment->expiration_at)->format('M d, Y g:i A') : null;
                $statusMeta = [
                    0 => 'Pending',
                    1 => 'Approved',
                    2 => 'Rejected',
                ];

                return [
                    'id' => $payment->id,
                    'member' => $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : 'Unknown member',
                    'role' => $member && $member->role ? ($member->role->name ?? null) : null,
                    'email' => $member ? ($member->email ?? null) : null,
                    'phone' => $member ? ($member->phone_number ?? null) : null,
                    'membership' => $membership ? $membership->name : 'Membership unavailable',
                    'price' => $membership && isset($membership->price) ? number_format((float) $membership->price, 2) : null,
                    'status' => $statusMeta[$payment->isapproved] ?? 'Pending',
                    'purchased' => $purchasedAt,
                    'expires' => $expiresAt,
                    'archive' => (int) ($payment->is_archive ?? 0) === 1 ? 'Archived' : 'Active',
                ];
            })->values();

            $printPayload = [
                'title' => $showArchived ? 'Archived payments history' : 'Payments history',
                'generated_at' => now()->format('M d, Y g:i A'),
                'filters' => [
                    'search' => $filters['search'] ?? '',
                    'membership_id' => $filters['membership_id'] ?? null,
                    'status' => $filters['status'] ?? null,
                    'start' => $filters['start_date'] ?? null,
                    'end' => $filters['end_date'] ?? null,
                    'archived' => $showArchived,
                ],
                'count' => $printItems->count(),
                'items' => $printItems,
            ];
        @endphp

        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="title mb-1">Payments History</h2>
                    <p class="text-muted mb-0 small">Past membership payments with status and archive visibility.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center h-100">
                    <form action="{{ route('admin.history.payments.print') }}" method="POST" id="payment-history-print-form">
                        @csrf
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        <input type="hidden" name="membership_id" value="{{ $filters['membership_id'] ?? '' }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                        <input type="hidden" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                        <input type="hidden" name="show_archived" value="{{ $showArchived ? 1 : 0 }}">
                        <button
                            type="submit"
                            class="btn btn-danger"
                            id="payment-history-print-submit"
                            data-print='@json($printPayload)'
                            aria-label="Open printable/PDF view of filtered payments"
                        >
                            <i class="fa-solid fa-print me-2"></i>Print
                            <span id="payment-history-print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </form>
                    <a href="{{ route('admin.staff-account-management.membership-payments') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-wallet me-2"></i>Live payments
                    </a>
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('admin.history.payments', array_merge(request()->except(['show_archived', 'page']), [])) }}"
                        >
                            <i class="fa-solid fa-rotate-left me-2"></i>Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('admin.history.payments', array_merge(request()->except(['page']), ['show_archived' => 1])) }}"
                        >
                            <i class="fa-solid fa-box-archive me-2"></i>View archived
                        </a>
                    @endif
                </div>
            </div>

            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">History</span>
                                <h4 class="fw-semibold mb-1">Membership payments</h4>
                                <p class="text-muted mb-0">Filter by member, plan, status, or purchase window to review past payments.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Records</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['total'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Unique members</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['members'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Memberships</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['memberships'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('admin.history.payments') }}" method="GET" id="payment-history-filter-form" class="mt-3">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif
                            <input type="hidden" name="status" id="payment-history-status-filter" value="{{ $activeStatus }}">

                            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                                @foreach ($statusLabels as $key => $label)
                                    @php
                                        $count = $statusTallies[$key] ?? null;
                                    @endphp
                                    <button
                                        type="button"
                                        class="btn btn-sm rounded-pill px-3 payment-history-status-chip {{ $activeStatus === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                        data-status="{{ $key }}"
                                        aria-label="Filter payments by {{ strtolower($label['label']) }}"
                                    >
                                        {{ $label['label'] }}
                                        @if(!is_null($count))
                                            <span class="badge bg-transparent {{ $activeStatus === $key ? 'text-white' : 'text-dark' }} fw-semibold ms-2">{{ $count }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 260px;">
                                    <div class="position-relative">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                        <input
                                            type="search"
                                            id="search"
                                            name="search"
                                            class="form-control rounded-pill ps-5"
                                            value="{{ $filters['search'] ?? '' }}"
                                            placeholder="Search member, membership, or ID"
                                            aria-label="Search payments"
                                        >
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if ($hasFilters)
                                        <a href="{{ route('admin.history.payments', $showArchived ? ['show_archived' => 1] : []) }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                                    @endif

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#paymentHistoryFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="paymentHistoryFiltersModal" tabindex="-1" aria-labelledby="paymentHistoryFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="paymentHistoryFiltersModalLabel">Advanced filters</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex flex-column gap-4">
                                                <div>
                                                    <span class="text-muted text-uppercase small fw-semibold d-block">Quick ranges</span>
                                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill payment-history-range-chip" data-range="last-week">Last week</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill payment-history-range-chip" data-range="last-month">Last month</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill payment-history-range-chip" data-range="last-year">Last year</button>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label for="membership_id" class="form-label text-muted text-uppercase small mb-1">Membership</label>
                                                    <select id="membership_id" name="membership_id" class="form-select rounded-3">
                                                        <option value="">All memberships</option>
                                                        @foreach ($membershipOptions as $membership)
                                                            <option
                                                                value="{{ $membership->id }}"
                                                                {{ (string) ($filters['membership_id'] ?? '') === (string) $membership->id ? 'selected' : '' }}
                                                            >
                                                                {{ $membership->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label for="status" class="form-label text-muted text-uppercase small mb-1">Status</label>
                                                    <select id="status" name="status_display" class="form-select rounded-3">
                                                        <option value="all" {{ $activeStatus === 'all' ? 'selected' : '' }}>All</option>
                                                        <option value="approved" {{ $activeStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                                                        <option value="pending" {{ $activeStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                                        <option value="rejected" {{ $activeStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <span class="form-label text-muted text-uppercase small d-block mb-2">Purchased range</span>
                                                    <div class="row g-2">
                                                        <div class="col-12 col-sm-6">
                                                            <label for="payment-history-start-date" class="form-label small text-muted mb-1">From</label>
                                                            <input
                                                                type="date"
                                                                id="payment-history-start-date"
                                                                name="start_date"
                                                                class="form-control rounded-3"
                                                                value="{{ $filters['start_date'] ?? '' }}"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label for="payment-history-end-date" class="form-label small text-muted mb-1">To</label>
                                                            <input
                                                                type="date"
                                                                id="payment-history-end-date"
                                                                class="form-control rounded-3"
                                                                name="end_date"
                                                                value="{{ $filters['end_date'] ?? '' }}"
                                                            />
                                                        </div>
                                                    </div>
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
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Member</th>
                                        <th>Contact</th>
                                        <th>Membership</th>
                                        <th>Status</th>
                                        <th>Purchased</th>
                                        <th>Expires</th>
                                        <th>Archive</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($payments as $index => $payment)
                                        @php
                                            $member = $payment->user;
                                            $membership = $payment->membership;
                                            $purchasedAt = $payment->created_at ? $payment->created_at->format('M d, Y g:i A') : '—';
                                            $expiresAt = $payment->expiration_at ? \Carbon\Carbon::parse($payment->expiration_at)->format('M d, Y g:i A') : '—';
                                            $fullName = $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : '';
                                            $statusValue = $payment->isapproved;
                                            $statusMeta = [
                                                0 => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                                                1 => ['label' => 'Approved', 'class' => 'bg-success'],
                                                2 => ['label' => 'Rejected', 'class' => 'bg-danger'],
                                            ][$statusValue] ?? ['label' => 'Pending', 'class' => 'bg-warning text-dark'];
                                            $archiveLabel = (int) ($payment->is_archive ?? 0) === 1 ? 'Archived' : 'Active';
                                        @endphp
                                        <tr>
                                            <td>{{ $payment->id ?? '—' }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $fullName !== '' ? $fullName : 'Unknown member' }}</div>
                                                @if($member && $member->role)
                                                    <div class="text-muted small">{{ $member->role->name ?? '' }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <div>{{ $member ? ($member->email ?? '—') : '—' }}</div>
                                                <div class="text-muted small">{{ $member ? ($member->phone_number ?? '—') : '—' }}</div>
                                            </td>
                                            <td>
                                                @if($membership)
                                                    <div class="fw-semibold">{{ $membership->name }}</div>
                                                    <div class="text-muted small">
                                                        @php
                                                            $currency = $membership->currency ?? 'PHP';
                                                            $price = $membership->price ?? 0;
                                                        @endphp
                                                        {{ $currency }} {{ number_format((float) $price, 2) }}
                                                    </div>
                                                @else
                                                    <span class="text-muted">Membership unavailable</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $statusMeta['class'] }} px-3 py-2">
                                                    {{ $statusMeta['label'] }}
                                                </span>
                                            </td>
                                            <td>{{ $purchasedAt }}</td>
                                            <td>{{ $expiresAt }}</td>
                                            <td>
                                                <span class="badge {{ $archiveLabel === 'Archived' ? 'bg-secondary' : 'bg-success' }} px-3 py-2">
                                                    {{ $archiveLabel }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                    @if($member)
                                                        <a
                                                            href="{{ route('admin.gym-management.members.view', $member->id) }}"
                                                            class="btn btn-outline-secondary btn-sm"
                                                        >
                                                            Member
                                                        </a>
                                                    @endif
                                                    <a
                                                        href="{{ route('admin.staff-account-management.membership-payments.view', $payment->id) }}"
                                                        class="btn btn-outline-primary btn-sm"
                                                    >
                                                        Payment
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted">
                                                No payment records found{{ $hasFilters ? ' for the selected filters.' : '.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">
                            {{ $payments->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($payrollRuns) && $payrollRuns->isNotEmpty())
                <div class="col-12 mt-4">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-4">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Staff Payrolls</span>
                                    <h4 class="fw-semibold mb-1">Processed payroll runs</h4>
                                    <p class="text-muted mb-0">Recent payrolls saved per staff and period.</p>
                                </div>
                                <div class="text-end">
                                    <span class="d-block text-muted small">Showing {{ $payrollRuns->count() }} recent runs</span>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Staff</th>
                                            <th>Period</th>
                                            <th>Hours</th>
                                            <th>Gross</th>
                                            <th>Net</th>
                                            <th>Processed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($payrollRuns as $run)
                                            @php
                                                $staff = $run->user;
                                                $name = $staff ? trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) : 'Unknown';
                                                $processedAt = $run->processed_at ? $run->processed_at->format('M d, Y g:i A') : ($run->created_at ? $run->created_at->format('M d, Y g:i A') : '—');
                                            @endphp
                                            <tr>
                                                <td>{{ $name }}</td>
                                                <td>{{ $run->period_month }}</td>
                                                <td>{{ number_format($run->total_hours, 2) }} hrs</td>
                                                <td>₱{{ number_format($run->gross_pay, 2) }}</td>
                                                <td class="text-success fw-semibold">₱{{ number_format($run->net_pay, 2) }}</td>
                                                <td>{{ $processedAt }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printButton = document.getElementById('payment-history-print-submit');
            const printForm = document.getElementById('payment-history-print-form');
            const printLoader = document.getElementById('payment-history-print-loader');

            function buildFilters(filters) {
                const chips = [];
                if (filters.search) chips.push(`Search: ${filters.search}`);
                if (filters.membership_id) chips.push(`Membership ID: ${filters.membership_id}`);
                if (filters.status && filters.status !== 'all') chips.push(`Status: ${filters.status}`);
                if (filters.archived) chips.push('Archived records');
                if (filters.start || filters.end) chips.push(`Purchased: ${filters.start || '—'} → ${filters.end || '—'}`);
                return chips.map((chip) => `<span class="pill">${chip}</span>`).join('') || '<span class="muted">No filters applied</span>';
            }

            function buildRows(items) {
                return (items || []).map((item) => {
                    const role = item.role ? `<div class="muted">${item.role}</div>` : '';
                    const phone = item.phone ? `<div class="muted">${item.phone}</div>` : '';
                    const price = item.price ? `PHP ${item.price}` : '—';

                    return `
                        <tr>
                            <td>${item.id ?? '—'}</td>
                            <td>
                                <div class="fw">${item.member || '—'}</div>
                                ${role}
                            </td>
                            <td>
                                <div>${item.email || '—'}</div>
                                ${phone}
                            </td>
                            <td>${item.membership || '—'}</td>
                            <td>${price}</td>
                            <td>${item.status || '—'}</td>
                            <td>${item.purchased || '—'}</td>
                            <td>${item.expires || '—'}</td>
                            <td>${item.archive || '—'}</td>
                        </tr>
                    `;
                }).join('');
            }

            function renderPrintWindow(payload) {
                const items = payload.items || [];
                const filters = payload.filters || {};
                const rows = buildRows(items);
                const html = `
                    <!doctype html>
                    <html>
                        <head>
                            <title>${payload.title || 'Payments history'}</title>
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
                                .fw { font-weight: 700; }
                            </style>
                        </head>
                        <body>
                            <div class="sheet">
                                <div class="header">
                                    <div>
                                        <h1 class="title">${payload.title || 'Payments history'}</h1>
                                        <div class="muted">Generated ${payload.generated_at || ''}</div>
                                        <div class="muted">Showing ${payload.count || 0} record(s)</div>
                                    </div>
                                </div>
                                <div class="pill-row">${buildFilters(filters)}</div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Member</th>
                                            <th>Contact</th>
                                            <th>Membership</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Purchased</th>
                                            <th>Expires</th>
                                            <th>Archive</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows || '<tr><td colspan="9" style="text-align:center; padding:16px;">No payments available for this view.</td></tr>'}
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

            if (printButton && printForm) {
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
                        printForm.submit();
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('payment-history-filter-form');
            if (!form) {
                return;
            }

            const rangeButtons = form.querySelectorAll('.payment-history-range-chip');
            const startInput = document.getElementById('payment-history-start-date');
            const endInput = document.getElementById('payment-history-end-date');
            const statusInput = document.getElementById('payment-history-status-filter');
            const statusSelect = document.getElementById('status');
            const statusButtons = form.querySelectorAll('.payment-history-status-chip');

            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function applyRange(range) {
                const today = new Date();
                const end = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                const start = new Date(end);

                if (range === 'last-week') {
                    start.setDate(start.getDate() - 7);
                } else if (range === 'last-month') {
                    start.setMonth(start.getMonth() - 1);
                } else if (range === 'last-year') {
                    start.setFullYear(start.getFullYear() - 1);
                }

                if (startInput) startInput.value = formatDate(start);
                if (endInput) endInput.value = formatDate(end);
            }

            function setActiveStatus(status) {
                if (statusInput) statusInput.value = status;
                if (statusSelect) statusSelect.value = status;

                statusButtons.forEach((btn) => {
                    const isActive = btn.dataset.status === status;
                    btn.classList.toggle('btn-dark', isActive);
                    btn.classList.toggle('btn-outline-secondary', !isActive);
                });
            }

            statusButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const selectedStatus = this.dataset.status || '';
                    setActiveStatus(selectedStatus);
                    form.submit();
                });
            });

            if (statusSelect) {
                statusSelect.addEventListener('change', function () {
                    setActiveStatus(this.value || '');
                });
            }

            if (statusInput && statusInput.value) {
                setActiveStatus(statusInput.value);
            }

            rangeButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const range = this.dataset.range;
                    applyRange(range);
                });
            });
        });
    </script>
@endsection
