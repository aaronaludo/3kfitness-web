@extends('layouts.admin')
@section('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('title', 'Members Data')

@section('content')
    <style>
        .manual-clock-modal .modal-content {
            border-radius: 22px;
            border: none;
            background: #f6f5fb;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
        }
        .manual-clock-modal .modal-header {
            border-bottom: none;
        }
        .manual-clock-hero {
            text-align: center;
        }
        .manual-clock-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 12px;
            border: 1px solid transparent;
        }
        .manual-clock-icon--success {
            background: rgba(34, 197, 94, 0.15);
            color: #16a34a;
            border-color: rgba(34, 197, 94, 0.35);
        }
        .manual-clock-icon--danger {
            background: rgba(239, 68, 68, 0.12);
            color: #dc2626;
            border-color: rgba(239, 68, 68, 0.35);
        }
        .manual-clock-icon--warning {
            background: rgba(245, 158, 11, 0.12);
            color: #b45309;
            border-color: rgba(245, 158, 11, 0.35);
        }
        .manual-clock-icon--loading {
            background: rgba(148, 163, 184, 0.18);
            color: #475569;
            border-color: rgba(148, 163, 184, 0.35);
        }
        .manual-clock-title {
            font-weight: 800;
            margin-bottom: 4px;
        }
        .manual-clock-subtitle {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 16px;
        }
        .manual-clock-member-card {
            background: #fff;
            border-radius: 16px;
            padding: 12px 14px;
            display: flex;
            gap: 12px;
            align-items: center;
            border: 1px solid #eceef6;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }
        .manual-clock-avatar {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            object-fit: cover;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            flex: 0 0 auto;
        }
        .manual-clock-member-info {
            flex: 1;
            min-width: 0;
        }
        .manual-clock-member-name {
            font-weight: 700;
            margin-bottom: 2px;
        }
        .manual-clock-member-meta {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 2px;
            word-break: break-word;
        }
        .manual-clock-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 6px;
        }
        .manual-clock-chip {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .manual-clock-chip--active {
            background: rgba(34, 197, 94, 0.15);
            color: #15803d;
            border-color: rgba(34, 197, 94, 0.35);
        }
        .manual-clock-chip--inactive {
            background: rgba(148, 163, 184, 0.15);
            color: #64748b;
            border-color: rgba(148, 163, 184, 0.35);
        }
        @media (max-width: 575px) {
            .manual-clock-member-card {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
    <div class="container-fluid">
        <div class="row">
            @php
                $showArchived = request()->boolean('show_archived');
                $printSource = $showArchived ? $archivedData : $gym_members;
                $printAllSource = $showArchived ? ($printAllArchived ?? collect()) : ($printAllActive ?? collect());
                $printMembers = collect($printSource->items() ?? [])->map(function ($item) use ($current_time) {
                    $latestMembershipPayment = optional($item->membershipPayments)->first();
                    $membership = optional($latestMembershipPayment)->membership;
                    $paymentIsArchived = (int) optional($latestMembershipPayment)->is_archive === 1;
                    $membershipIsArchived = (int) optional($membership)->is_archive === 1;
                    $membershipActive = $latestMembershipPayment && !$paymentIsArchived && !$membershipIsArchived;
                    $membershipName = $membershipActive ? (optional($membership)->name ?? 'No Membership') : 'No Membership';
                    $approvedBy = $membershipActive ? optional($latestMembershipPayment)->created_by : null;
                    $expirationAt   = $membershipActive ? optional($latestMembershipPayment)->expiration_at : null;
                    $membershipStatus = $membershipActive ? 'Active membership' : 'No membership';

                    $memberName = trim((optional($item)->first_name ?? '') . ' ' . (optional($item)->last_name ?? ''));

                    return [
                        'id' => $item->id,
                        'user_code' => $item->user_code,
                        'membership' => $membershipName,
                        'membership_status' => $membershipStatus,
                        'membership_expires' => $expirationAt ? \Carbon\Carbon::parse($expirationAt)->format('M j, Y g:i A') : 'No Expiration Date',
                        'name' => $memberName ?: '—',
                        'phone' => $item->phone_number,
                        'email' => $item->email,
                        'created' => optional($item->created_at)->format('M j, Y g:i A') ?: '',
                        'updated' => optional($item->updated_at)->format('M j, Y g:i A') ?: '',
                        'approved_by' => $approvedBy ?: 'Pending staff approval',
                    ];
                })->values();

                $printPayload = [
                    'title' => $showArchived ? 'Archived members' : 'Member directory',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => request('name'),
                        'membership_status' => request('membership_status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                    ],
                    'count' => $printMembers->count(),
                    'items' => $printMembers,
                ];

                $printAllMembers = collect($printAllSource ?? [])->map(function ($item) use ($current_time) {
                    $latestMembershipPayment = optional($item->membershipPayments)->first();
                    $membership = optional($latestMembershipPayment)->membership;
                    $paymentIsArchived = (int) optional($latestMembershipPayment)->is_archive === 1;
                    $membershipIsArchived = (int) optional($membership)->is_archive === 1;
                    $membershipActive = $latestMembershipPayment && !$paymentIsArchived && !$membershipIsArchived;
                    $membershipName = $membershipActive ? (optional($membership)->name ?? 'No Membership') : 'No Membership';
                    $approvedBy = $membershipActive ? optional($latestMembershipPayment)->created_by : null;
                    $expirationAt   = $membershipActive ? optional($latestMembershipPayment)->expiration_at : null;
                    $membershipStatus = $membershipActive ? 'Active membership' : 'No membership';

                    $memberName = trim((optional($item)->first_name ?? '') . ' ' . (optional($item)->last_name ?? ''));

                    return [
                        'id' => $item->id,
                        'user_code' => $item->user_code,
                        'membership' => $membershipName,
                        'membership_status' => $membershipStatus,
                        'membership_expires' => $expirationAt ? \Carbon\Carbon::parse($expirationAt)->format('M j, Y g:i A') : 'No Expiration Date',
                        'name' => $memberName ?: '—',
                        'phone' => $item->phone_number,
                        'email' => $item->email,
                        'created' => optional($item->created_at)->format('M j, Y g:i A') ?: '',
                        'updated' => optional($item->updated_at)->format('M j, Y g:i A') ?: '',
                        'approved_by' => $approvedBy ?: 'Pending staff approval',
                    ];
                })->values();

                $printAllPayload = [
                    'title' => $showArchived ? 'Archived members (all pages)' : 'Member directory (all pages)',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => request('name'),
                        'membership_status' => request('membership_status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                        'scope' => 'all',
                    ],
                    'count' => $printAllMembers->count(),
                    'items' => $printAllMembers,
                ];
            @endphp
            <div class="col-lg-12 d-flex justify-content-between align-items-center flex-wrap gap-3 my-4">
                <div><h1 class="title">Members Data</h1></div>
                <div class="d-flex align-items-center">
                    {{-- <form action="{{ route('admin.gym-management.members.print') }}" method="POST" id="print-form">
                        @csrf
                        <button class="btn btn-danger ms-2" type="submit" id="print-submit-button">
                            <i class="fa-solid fa-print"></i>&nbsp;&nbsp;&nbsp;
                            <span id="print-loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form> --}}
                    <a class="btn btn-danger" href="{{ route('admin.gym-management.members.create') }}">
                        <i class="fa-solid fa-plus"></i>&nbsp;&nbsp;&nbsp;Walk-in Registration  
                    </a>
                    <form action="{{ route('admin.gym-management.members.print') }}" method="POST" id="print-form">
                        @csrf
                        <div>
                          <input
                            type="hidden"
                            name="created_start"
                            class="form-control"
                            value="{{ request('start_date') }}"
                            aria-label="Start date"
                          />
                          <input
                            type="hidden"
                            name="created_end"
                            class="form-control"
                            value="{{ request('end_date') }}"
                            aria-label="End date"
                          />
                      
                          <input type="hidden" name="name" value="{{ request('name') }}">
                          <input type="hidden" name="membership_status" value="{{ request('membership_status', 'all') }}">
                      
                          <button
                            class="btn btn-md btn-danger ms-2"
                            type="submit"
                            id="print-submit-button"
                            data-print='@json($printPayload)'
                            data-print-all='@json($printAllPayload)'
                            aria-label="Open printable/PDF view of filtered members"
                          >
                            <i class="fa-solid fa-print"></i>
                            <span id="print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                          </button>
                        </div>
                    </form>
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.gym-management.members', request()->except(['show_archived', 'page', 'archive_page'])) }}"
                        >
                            <i class="fa-solid fa-rotate-left"></i>&nbsp;&nbsp;Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.gym-management.members', array_merge(request()->except(['page', 'archive_page']), ['show_archived' => 1])) }}"
                        >
                            <i class="fa-solid fa-box-archive"></i>&nbsp;&nbsp;View archived
                        </a>
                    @endif    
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const printButton = document.getElementById('print-submit-button');
                    const printForm = document.getElementById('print-form');
                    const printLoader = document.getElementById('print-loader');

                    function buildFilters(filters) {
                        const chips = [];
                        if (filters.show_archived) chips.push({ value: 'Archived view' });
                        if (filters.membership_status && filters.membership_status !== 'all') chips.push({ label: 'Membership', value: filters.membership_status });
                        if (filters.search) {
                            chips.push({
                                label: 'Search',
                                value: filters.search,
                            });
                        }
                        if (filters.start || filters.end) {
                            chips.push({ label: 'Date', value: `${filters.start || '—'} → ${filters.end || '—'}` });
                        }
                        return chips;
                    }

                    function buildRows(items) {
                        return items.map((item) => {
                            return [
                                `<div class="fw">${item.user_code || item.id || '—'}</div><div class="muted">ID: ${item.id ?? '—'}</div>`,
                                `<div class="fw">${item.name || '—'}</div><div class="muted">${item.email || ''}</div><div class="muted">${item.phone || ''}</div>`,
                                `<div>${item.membership || 'No membership'}</div><div class="muted">${item.membership_status || ''}</div><div class="muted">Expires: ${item.membership_expires || '—'}</div>`,
                                `<div>${item.created || ''}</div><div class="muted">${item.updated || ''}</div><div class="muted">Approved by: ${item.approved_by || 'Pending staff approval'}</div>`,
                            ];
                        });
                    }

                    function renderPrintWindow(payload) {
                        const rawItems = payload && payload.items ? payload.items : [];
                        const items = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
                        const filters = buildFilters(payload.filters || {});
                        const headers = ['#', 'Member', 'Membership', 'Audit'];
                        const rows = buildRows(items);

                        return window.PrintPreview
                            ? PrintPreview.tryOpen(payload, headers, rows, filters)
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
                                printForm.submit();
                            }

                            printButton.disabled = false;
                            if (printLoader) printLoader.classList.add('d-none');
                        });
                    }
                });
            </script>

            @php
                $membershipStatus = request('membership_status', 'all');
                $statusTallies = $statusTallies ?? [];
                $statusOptions = [
                    'all' => [
                        'label' => 'All members',
                        'count' => $statusTallies['all'] ?? null,
                    ],
                    'with' => [
                        'label' => 'With membership',
                        'count' => $statusTallies['with'] ?? null,
                    ],
                    'none' => [
                        'label' => 'No membership',
                        'count' => $statusTallies['none'] ?? null,
                    ],
                ];
                $advancedFiltersOpen = request()->filled('start_date') || request()->filled('end_date');
            @endphp

            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Overview</span>
                                <h4 class="fw-semibold mb-1">Member directory</h4>
                                <p class="text-muted mb-0">Filter by membership status or pick a quick date range to focus on recent signups.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">
                                    @if ($showArchived)
                                        Showing {{ $archivedData->total() }} archived members
                                    @else
                                        Showing {{ $gym_members->total() }} results
                                    @endif
                                </span>
                            </div>
                        </div>

                        <form action="{{ route('admin.gym-management.members') }}" method="GET" id="member-filter-form" class="mt-4">
                            <input type="hidden" name="membership_status" id="member-status-filter" value="{{ $membershipStatus }}">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @foreach ($statusOptions as $key => $option)
                                        <button
                                            type="button"
                                            class="membership-chip btn btn-sm rounded-pill px-3 {{ $membershipStatus === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                            data-status="{{ $key }}"
                                        >
                                            {{ $option['label'] }}
                                            @if(!is_null($option['count']))
                                                <span class="badge bg-transparent {{ $membershipStatus === $key ? 'text-white' : 'text-dark' }} fw-semibold ms-2">{{ $option['count'] }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <div class="flex-grow-1 flex-lg-grow-0" style="min-width: 240px;">
                                        <div class="position-relative">
                                            <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                            <input
                                                type="search"
                                                class="form-control rounded-pill ps-5"
                                                name="name"
                                                placeholder="Search members"
                                                value="{{ request('name') }}"
                                                aria-label="Search members"
                                            />
                                        </div>
                                    </div>

                                    <a
                                        href="{{ $showArchived ? route('admin.gym-management.members', ['show_archived' => 1]) : route('admin.gym-management.members') }}"
                                        class="btn btn-link text-decoration-none text-muted px-0"
                                    >
                                        Reset
                                    </a>

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#memberFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="memberFiltersModal" tabindex="-1" aria-labelledby="memberFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="memberFiltersModalLabel">Advanced filters</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex flex-column gap-4">
                                                <div>
                                                    <span class="text-muted text-uppercase small fw-semibold d-block">Quick ranges</span>
                                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill range-chip" data-range="last-week">Last week</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill range-chip" data-range="last-month">Last month</button>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill range-chip" data-range="last-year">Last year</button>
                                                    </div>
                                                </div>

                                                <div>
                                                    <span class="form-label text-muted text-uppercase small d-block mb-2">Date range</span>
                                                    <div class="row g-2">
                                                        <div class="col-12 col-sm-6">
                                                            <label for="start-date" class="form-label small text-muted mb-1">Start date</label>
                                                            <input
                                                                type="date"
                                                                id="start-date"
                                                                class="form-control rounded-3"
                                                                name="start_date"
                                                                value="{{ request('start_date') }}"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label for="end-date" class="form-label small text-muted mb-1">End date</label>
                                                            <input
                                                                type="date"
                                                                id="end-date"
                                                                class="form-control rounded-3"
                                                                name="end_date"
                                                                value="{{ request('end_date') }}"
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

            <div class="col-lg-12">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @php
                    $actionFeedbackMessage = session('success') ?? session('error');
                    $actionFeedbackIsError = session()->has('error');
                @endphp
                @if ($actionFeedbackMessage)
                    <div class="modal fade" id="actionFeedbackModal" tabindex="-1" aria-labelledby="actionFeedbackModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-body p-4 text-center">
                                    <div class="display-5 mb-3 {{ $actionFeedbackIsError ? 'text-danger' : 'text-success' }}">
                                        <i class="fa-solid {{ $actionFeedbackIsError ? 'fa-circle-exclamation' : 'fa-circle-check' }}"></i>
                                    </div>
                                    <h5 class="fw-semibold mb-2" id="actionFeedbackModalLabel">
                                        {{ $actionFeedbackIsError ? 'Something went wrong' : 'Action completed' }}
                                    </h5>
                                    <p class="text-muted mb-0">{{ $actionFeedbackMessage }}</p>
                                </div>
                                <div class="modal-footer border-0 justify-content-center pb-4 pt-0">
                                    <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">Got it</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if (!$showArchived)
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">


                                <table class="table table-hover" id="member-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-column="id"># <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="user_code">User Code <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="membership_name">Membership Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="expiration_date">Membership Expiration Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="name">Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="phone_number">Phone Number <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="email">Email <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="updated_date">Updated Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_by">Approved By <i class="fa fa-sort"></i></th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($gym_members as $item)
                                        @php
                                            $latestMembershipPayment = $item->membershipPayments()
                                                ->where('isapproved', 1)
                                                ->where('expiration_at', '>=', $current_time)
                                                ->where('is_archive', 0)
                                                ->with('membership')
                                                ->orderBy('created_at', 'desc')
                                                ->first();

                                            $membership = optional($latestMembershipPayment)->membership;
                                            $paymentIsArchived = (int) optional($latestMembershipPayment)->is_archive === 1;
                                            $membershipIsArchived = (int) optional($membership)->is_archive === 1;
                                            $membershipActive = $latestMembershipPayment && !$paymentIsArchived && $membership && !$membershipIsArchived;
                                            $membershipName = $membershipActive ? $membership->name : 'No Membership';
                                            $expirationAt   = $membershipActive ? optional($latestMembershipPayment)->expiration_at : 'No Expiration Date';

                                            $hasMembership = $membershipActive;
                                            $membershipStatusLabel = $membershipActive ? 'Active membership' : 'No active membership';
                                            $membershipExpiresLabel = $membershipActive && $latestMembershipPayment && $latestMembershipPayment->expiration_at
                                                ? \Carbon\Carbon::parse($latestMembershipPayment->expiration_at)->format('F j, Y g:iA')
                                                : ($membershipActive ? 'No Expiration Date' : 'No active membership');
                                            $membershipId = $membershipActive && $membership ? $membership->id : '';

                                            $approvedBy = $membershipActive
                                                ? optional($latestMembershipPayment)->created_by
                                                : 'Pending staff approval';
                                            $profilePicture = $item->profile_picture
                                                ? asset($item->profile_picture)
                                                : asset('assets/images/profile-45x45.png');
                                            $memberCode = $item->user_code ?: $item->id;
                                        @endphp

                                        {{-- UPDATED START: mark each row for filtering --}}
                                        <tr data-has-membership="{{ $hasMembership ? '1' : '0' }}">
                                        {{-- UPDATED END --}}
                                            <td>{{ $item->id }}</td>
                                            <td><span class="text-muted small">{{ optional($item)->user_code ?? '—' }}</span></td>

                                            {{-- UPDATED START: show badge + consistent "No Membership" label --}}
                                            <td>
                                                @if($hasMembership)
                                                    <span class="badge bg-success">{{ $membershipName }}</span>
                                                @else
                                                    <span class="badge bg-secondary">No Membership</span>
                                                @endif
                                            </td>
                                            {{-- UPDATED END --}}

                                            <td>
                                                @if ($latestMembershipPayment && $latestMembershipPayment->expiration_at)
                                                    {{ \Carbon\Carbon::parse($latestMembershipPayment->expiration_at)->format('F j, Y g:iA') }}
                                                @else
                                                    {{ $expirationAt }}
                                                @endif
                                            </td>

                                            <td>{{ $item->first_name }} {{ $item->last_name }}</td>
                                            <td>{{ $item->phone_number }}</td>
                                            <td>{{ $item->email }}</td>
                                            <td>{{ optional($item->created_at)->format('F j, Y g:iA') }}</td>
                                            <td>{{ optional($item->updated_at)->format('F j, Y g:iA') }}</td>
                                            <td>{{ $approvedBy }}</td>
                                            <td>
                                                <div class="d-flex flex-wrap align-items-center gap-2">
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="Manual attendance actions">
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-success manual-clock-button"
                                                            data-email="{{ $item->email }}"
                                                            data-name="{{ $item->first_name }} {{ $item->last_name }}"
                                                            data-phone="{{ $item->phone_number }}"
                                                            data-member-code="{{ $memberCode }}"
                                                            data-membership="{{ $membershipName }}"
                                                            data-membership-active="{{ $hasMembership ? '1' : '0' }}"
                                                            data-avatar="{{ $profilePicture }}"
                                                            data-action="clockin"
                                                        >
                                                            <i class="fa-regular fa-clock me-1"></i>Clock In
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-secondary manual-clock-button"
                                                            data-email="{{ $item->email }}"
                                                            data-name="{{ $item->first_name }} {{ $item->last_name }}"
                                                            data-phone="{{ $item->phone_number }}"
                                                            data-member-code="{{ $memberCode }}"
                                                            data-membership="{{ $membershipName }}"
                                                            data-membership-active="{{ $hasMembership ? '1' : '0' }}"
                                                            data-avatar="{{ $profilePicture }}"
                                                            data-action="clockout"
                                                        >
                                                            <i class="fa-solid fa-right-from-bracket me-1"></i>Clock Out
                                                        </button>
                                                    </div>

                                                    @if (!$hasMembership)
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-primary btn-sm renew-membership-button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#renewMembershipModal"
                                                            data-member-id="{{ $item->id }}"
                                                            data-member-name="{{ $item->first_name }} {{ $item->last_name }}"
                                                            data-member-email="{{ $item->email }}"
                                                            data-member-phone="{{ $item->phone_number }}"
                                                            data-member-code="{{ $memberCode }}"
                                                            data-membership-status="{{ $membershipStatusLabel }}"
                                                            data-membership-name="{{ $membershipName }}"
                                                            data-membership-expires="{{ $membershipExpiresLabel }}"
                                                            data-membership-id="{{ $membershipId }}"
                                                            data-membership-active="{{ $hasMembership ? '1' : '0' }}"
                                                        >
                                                            <i class="fa-solid fa-arrows-rotate me-1"></i>Renew Account
                                                        </button>
                                                    @endif

                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="action-button">
                                                            <a href="{{ route('admin.gym-management.members.view', $item->id) }}" title="View">
                                                                <i class="fa-solid fa-eye"></i>
                                                            </a>
                                                        </div>
                                                        <div class="action-button">
                                                            <a href="{{ route('admin.gym-management.members.edit', $item->id) }}" title="Edit">
                                                                <i class="fa-solid fa-pencil text-primary"></i>
                                                            </a>
                                                        </div>
    
                                                        <div class="action-button">
                                                            {{-- UPDATED START: keep delete only for "No Membership" --}}
                                                            @if (!$hasMembership)
                                                                <button
                                                                    type="button"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#deleteModal-{{ $item->id }}"
                                                                    data-id="{{ $item->id }}"
                                                                    title="Archive"
                                                                    style="background: none; border: none; padding: 0; cursor: pointer;"
                                                                >
                                                                    <i class="fa-solid fa-box-archive text-danger"></i>
                                                                </button>
                                                            @endif
                                                            {{-- UPDATED END --}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- Archive modal --}}
                                        <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel-{{ $item->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow rounded-4">
                                                    <div class="modal-header border-0 pb-0">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="badge bg-danger bg-opacity-10 text-danger rounded-circle p-3">
                                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                            </div>
                                                            <div>
                                                                <p class="text-uppercase text-muted small mb-1">Archive member</p>
                                                                <h5 class="fw-semibold mb-0" id="deleteModalLabel-{{ $item->id }}">
                                                                    {{ $item->first_name }} {{ $item->last_name }} ({{ $item->email ?? 'Member' }})
                                                                </h5>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('admin.gym-management.members.delete') }}" method="POST" id="main-form-{{ $item->id }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="id" value="{{ $item->id }}">
                                                        <div class="modal-body pt-3">
                                                            <div class="alert alert-danger bg-opacity-10 text-danger border-0 rounded-3">
                                                                Archiving will move this member to the archived list. You can restore the record later if needed.
                                                            </div>
                                                            <label class="form-label fw-semibold mt-2">Confirm with your password</label>
                                                            <div class="input-group">
                                                                <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                            <!--<button type="submit" class="btn btn-danger">Submit</button>-->
                                                            <button class="btn btn-danger" type="submit" id="submitButton-{{ $item->id }}">
                                                                <span id="loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                Archive member
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <script>
                                            document.getElementById('main-form-{{ $item->id }}')?.addEventListener('submit', function(e) {
                                                const submitButton = document.getElementById('submitButton-{{ $item->id }}');
                                                const loader = document.getElementById('loader-{{ $item->id }}');

                                                submitButton.disabled = true;
                                                loader.classList.remove('d-none');
                                            });
                                        </script>
                                        @endforeach
                                    </tbody>
                                </table>

                                {{ $gym_members->links() }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if ($showArchived)
                    <div class="box mt-5">
                        <div class="row">
                        <div class="col-lg-12">
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                <h4 class="fw-semibold mb-0">Archived Members</h4>
                                <span class="text-muted small">Showing {{ $archivedData->total() }} archived</span>
                            </div>
                            <div class="table-responsive mb-3">
                                <table class="table table-hover" id="archived-member-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>User Code</th>
                                            <th>Membership Name</th>
                                            <th>Membership Expiration Date</th>
                                            <th>Name</th>
                                            <th>Phone Number</th>
                                            <th>Email</th>
                                            <th>Created Date</th>
                                            <th>Updated Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($archivedData as $archive)
                                            @php
                                                $latestMembershipPayment = $archive->membershipPayments()
                                                    ->where('isapproved', 1)
                                                    ->where('expiration_at', '>=', $current_time)
                                                    ->where('is_archive', 0)
                                                    ->with('membership')
                                                    ->orderBy('created_at', 'desc')
                                                    ->first();

                                                $membership = optional($latestMembershipPayment)->membership;
                                                $paymentIsArchived = (int) optional($latestMembershipPayment)->is_archive === 1;
                                                $membershipIsArchived = (int) optional($membership)->is_archive === 1;
                                                $membershipActive = $latestMembershipPayment && !$paymentIsArchived && $membership && !$membershipIsArchived;
                                                $membershipName = $membershipActive ? $membership->name : 'No Membership';
                                                $expirationAt   = $membershipActive ? optional($latestMembershipPayment)->expiration_at : 'No Expiration Date';
                                            @endphp
                                            <tr>
                                                <td>{{ $archive->id }}</td>
                                                <td><span class="text-muted small">{{ optional($archive)->user_code ?? '—' }}</span></td>
                                                <td>
                                                    @if ($membershipName !== 'No Membership')
                                                        <span class="badge bg-success">{{ $membershipName }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">No Membership</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($latestMembershipPayment && $latestMembershipPayment->expiration_at)
                                                        {{ \Carbon\Carbon::parse($latestMembershipPayment->expiration_at)->format('F j, Y g:iA') }}
                                                    @else
                                                        {{ $expirationAt }}
                                                    @endif
                                                </td>
                                                <td>{{ $archive->first_name }} {{ $archive->last_name }}</td>
                                                <td>{{ $archive->phone_number }}</td>
                                                <td>{{ $archive->email }}</td>
                                                <td>{{ optional($archive->created_at)->format('F j, Y g:iA') }}</td>
                                                <td>{{ optional($archive->updated_at)->format('F j, Y g:iA') }}</td>
                                                <td class="action-button">
                                                    <div class="d-flex gap-2">
                                                        <button
                                                            type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#archiveRestoreModal-{{ $archive->id }}"
                                                            data-id="{{ $archive->id }}"
                                                            title="Restore"
                                                            style="background: none; border: none; padding: 0; cursor: pointer;"
                                                        >
                                                            <i class="fa-solid fa-rotate-left text-success"></i>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#archiveDeleteModal-{{ $archive->id }}"
                                                            data-id="{{ $archive->id }}"
                                                            title="Delete"
                                                            style="background: none; border: none; padding: 0; cursor: pointer;"
                                                        >
                                                            <i class="fa-solid fa-trash text-danger"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <div class="modal fade" id="archiveRestoreModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="archiveRestoreModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="archiveRestoreModalLabel-{{ $archive->id }}">Restore member ({{ $archive->email }})?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.gym-management.members.restore') }}" method="POST" id="archive-restore-modal-form-{{ $archive->id }}">
                                                            @csrf
                                                            <input type="hidden" name="id" value="{{ $archive->id }}">
                                                            <div class="modal-body">
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button class="btn btn-success" type="submit" id="archive-restore-modal-submit-button-{{ $archive->id }}">
                                                                    <span id="archive-restore-modal-loader-{{ $archive->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Restore
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="archiveDeleteModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="archiveDeleteModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="archiveDeleteModalLabel-{{ $archive->id }}">Delete archived member ({{ $archive->email }}) permanently?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.gym-management.members.delete') }}" method="POST" id="archive-delete-modal-form-{{ $archive->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="id" value="{{ $archive->id }}">
                                                            <div class="modal-body">
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button class="btn btn-danger" type="submit" id="archive-delete-modal-submit-button-{{ $archive->id }}">
                                                                    <span id="archive-delete-modal-loader-{{ $archive->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Submit
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                                document.getElementById('archive-restore-modal-form-{{ $archive->id }}')?.addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('archive-restore-modal-submit-button-{{ $archive->id }}');
                                                    const loader = document.getElementById('archive-restore-modal-loader-{{ $archive->id }}');

                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                            <script>
                                                document.getElementById('archive-delete-modal-form-{{ $archive->id }}')?.addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('archive-delete-modal-submit-button-{{ $archive->id }}');
                                                    const loader = document.getElementById('archive-delete-modal-loader-{{ $archive->id }}');

                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted">No archived members found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                {{ $archivedData->links() }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="renewMembershipModal" tabindex="-1" aria-labelledby="renewMembershipModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <p class="text-uppercase text-muted small mb-1">Membership renewal</p>
                        <h5 class="modal-title fw-semibold" id="renewMembershipModalLabel">Renew Account</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form
                    id="renewMembershipForm"
                    method="POST"
                    action="{{ route('admin.gym-management.members.update', 0) }}"
                    data-action-base="{{ url('/admin/members') }}"
                >
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                <div>
                                    <div class="text-muted small text-uppercase">Member</div>
                                    <div class="fw-semibold" id="renewMemberName">—</div>
                                    <div class="text-muted small" id="renewMemberEmail">—</div>
                                    <div class="text-muted small" id="renewMemberPhone">—</div>
                                    <div class="text-muted small" id="renewMemberCode">—</div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary" id="renewMembershipStatusBadge">Inactive</span>
                                    <div class="text-muted small mt-2" id="renewMembershipStatusText">—</div>
                                </div>
                            </div>

                            <div class="border rounded-3 p-3 bg-light">
                                <div class="row g-2">
                                    <div class="col-12 col-sm-6">
                                        <div class="text-muted small text-uppercase">Current membership</div>
                                        <div class="fw-semibold" id="renewMembershipName">No Membership</div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="text-muted small text-uppercase">Expiration</div>
                                        <div class="fw-semibold" id="renewMembershipExpires">—</div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="form-label fw-semibold" for="renewMembershipSelect">Renew to</label>
                                <select class="form-control" id="renewMembershipSelect" name="membership_id" required>
                                    <option value="" disabled selected>Select a membership</option>
                                    @foreach ($memberships as $membershipOption)
                                        <option value="{{ $membershipOption->id }}">{{ $membershipOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="alert alert-warning mb-0 d-none" id="renewMembershipNotice"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="renewMembershipSubmitBtn">
                            <span id="renewMembershipLoader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Renew membership
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div
        class="modal fade manual-clock-modal"
        id="manualClockModal"
        tabindex="-1"
        aria-labelledby="manualClockModalLabel"
        aria-hidden="true"
        data-default-avatar="{{ asset('assets/images/profile-45x45.png') }}"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header pb-0">
                    <h5 class="modal-title" id="manualClockModalLabel">Manual attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="manual-clock-hero">
                        <div class="manual-clock-icon manual-clock-icon--success" id="manualClockIcon">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <h4 class="manual-clock-title" id="manualClockTitle">Clocked In Successfully</h4>
                        <p class="manual-clock-subtitle" id="manualClockSubtitle">Attendance updated.</p>
                    </div>
                    <div class="manual-clock-member-card">
                        <img src="{{ asset('assets/images/profile-45x45.png') }}" alt="Member photo" class="manual-clock-avatar" id="manualClockAvatar">
                        <div class="manual-clock-member-info">
                            <div class="manual-clock-member-name" id="manualClockMemberName">Member</div>
                            <div class="manual-clock-member-meta" id="manualClockMemberEmail">member@email.com</div>
                            <div class="manual-clock-member-meta" id="manualClockMemberPhone">No phone</div>
                            <div class="manual-clock-chips">
                                <span class="manual-clock-chip" id="manualClockMemberCode">#---</span>
                                <span class="manual-clock-chip manual-clock-chip--inactive" id="manualClockMemberMembership">No Membership</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" id="manualClockCloseBtn">Close</button>
                    <button type="button" class="btn btn-danger px-4 d-none" id="manualClockConfirmBtn">Confirm Clock Out</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('member-filter-form');
            const manualClockButtons = document.querySelectorAll('.manual-clock-button');
            const manualClockModalEl = document.getElementById('manualClockModal');
            const manualClockIconEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockIcon') : null;
            const manualClockTitleEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockTitle') : null;
            const manualClockSubtitleEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockSubtitle') : null;
            const manualClockAvatarEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockAvatar') : null;
            const manualClockMemberNameEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockMemberName') : null;
            const manualClockMemberEmailEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockMemberEmail') : null;
            const manualClockMemberPhoneEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockMemberPhone') : null;
            const manualClockMemberCodeEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockMemberCode') : null;
            const manualClockMemberMembershipEl = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockMemberMembership') : null;
            const manualClockCloseBtn = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockCloseBtn') : null;
            const manualClockConfirmBtn = manualClockModalEl ? manualClockModalEl.querySelector('#manualClockConfirmBtn') : null;
            const manualClockModal = manualClockModalEl && typeof bootstrap !== 'undefined'
                ? new bootstrap.Modal(manualClockModalEl)
                : null;
            const csrfMeta = document.querySelector("meta[name='csrf-token']");
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
            const defaultAvatar = manualClockModalEl ? manualClockModalEl.getAttribute('data-default-avatar') : '';
            const pendingRequest = { action: null, member: null, button: null };

            const feedbackModalEl = document.getElementById('actionFeedbackModal');
            if (feedbackModalEl && typeof bootstrap !== 'undefined') {
                const feedbackModal = new bootstrap.Modal(feedbackModalEl);
                feedbackModal.show();
            }

            const iconMap = {
                success: { icon: 'fa-solid fa-circle-check', className: 'manual-clock-icon--success' },
                confirm: { icon: 'fa-regular fa-clock', className: 'manual-clock-icon--danger' },
                error: { icon: 'fa-solid fa-triangle-exclamation', className: 'manual-clock-icon--warning' },
                loading: { icon: 'fa-solid fa-spinner fa-spin', className: 'manual-clock-icon--loading' }
            };

            const normalizeMemberData = function (button) {
                const memberCode = button.dataset.memberCode || '';
                const formattedCode = memberCode.startsWith('#') ? memberCode : `#${memberCode || '---'}`;
                return {
                    name: button.dataset.name || 'Member',
                    email: button.dataset.email || 'No email',
                    phone: button.dataset.phone || 'No phone',
                    code: formattedCode,
                    membership: button.dataset.membership || 'No Membership',
                    membershipActive: button.dataset.membershipActive === '1',
                    avatar: button.dataset.avatar || defaultAvatar
                };
            };

            const updateMemberCard = function (member) {
                if (manualClockAvatarEl) {
                    manualClockAvatarEl.src = member.avatar || defaultAvatar || manualClockAvatarEl.src;
                }
                if (manualClockMemberNameEl) manualClockMemberNameEl.textContent = member.name || 'Member';
                if (manualClockMemberEmailEl) manualClockMemberEmailEl.textContent = member.email || 'No email';
                if (manualClockMemberPhoneEl) manualClockMemberPhoneEl.textContent = member.phone || 'No phone';
                if (manualClockMemberCodeEl) manualClockMemberCodeEl.textContent = member.code || '#---';
                if (manualClockMemberMembershipEl) {
                    manualClockMemberMembershipEl.textContent = member.membership || 'No Membership';
                    manualClockMemberMembershipEl.classList.remove('manual-clock-chip--active', 'manual-clock-chip--inactive');
                    manualClockMemberMembershipEl.classList.add(member.membershipActive ? 'manual-clock-chip--active' : 'manual-clock-chip--inactive');
                }
            };

            const setModalState = function (state, options) {
                const config = iconMap[state] || iconMap.success;
                if (manualClockIconEl) {
                    manualClockIconEl.className = `manual-clock-icon ${config.className}`;
                    manualClockIconEl.innerHTML = `<i class="${config.icon}"></i>`;
                }

                if (manualClockTitleEl) manualClockTitleEl.textContent = options.title || 'Attendance update';
                if (manualClockSubtitleEl) {
                    manualClockSubtitleEl.textContent = options.subtitle || '';
                    manualClockSubtitleEl.classList.toggle('d-none', !options.subtitle);
                }

                if (manualClockConfirmBtn && manualClockCloseBtn) {
                    const showConfirm = state === 'confirm';
                    manualClockConfirmBtn.classList.toggle('d-none', !showConfirm);
                    manualClockCloseBtn.textContent = showConfirm ? 'Cancel' : 'Close';
                }
            };

            const showManualClockModal = function () {
                if (manualClockModal) {
                    manualClockModal.show();
                } else if (manualClockTitleEl) {
                    alert(manualClockTitleEl.textContent);
                }
            };

            const isErrorMessage = function (message) {
                return /unable|invalid|no data|no valid|already|cannot|error|unexpected/i.test(message);
            };

            const setButtonLoading = function (button, loadingText) {
                if (!button) {
                    return;
                }
                if (!button.dataset.originalHtml) {
                    button.dataset.originalHtml = button.innerHTML;
                }
                button.disabled = true;
                button.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${loadingText}`;
            };

            const resetButton = function (button) {
                if (!button) {
                    return;
                }
                button.disabled = false;
                if (button.dataset.originalHtml) {
                    button.innerHTML = button.dataset.originalHtml;
                    delete button.dataset.originalHtml;
                }
            };

            const submitManualClock = function (action, member, button) {
                if (!csrfToken || !member.email || !action) {
                    updateMemberCard(member);
                    setModalState('error', {
                        title: 'Unable to update attendance',
                        subtitle: 'Missing required member details.'
                    });
                    showManualClockModal();
                    return;
                }

                setButtonLoading(button, action === 'clockout' ? 'Clocking out...' : 'Clocking in...');
                updateMemberCard(member);
                setModalState('loading', {
                    title: action === 'clockout' ? 'Clocking Out' : 'Clocking In',
                    subtitle: 'Please wait while we update attendance.'
                });
                showManualClockModal();

                fetch("{{ route('admin.staff-account-management.attendances.scanner2.fetch') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ result: member.email, action: action })
                })
                    .then(function (response) {
                        return response.json().catch(function () {
                            return { data: 'Unable to process attendance right now.' };
                        });
                    })
                    .then(function (data) {
                        const message = data && data.data ? data.data : `${member.name}'s attendance was updated.`;
                        const state = isErrorMessage(message) ? 'error' : 'success';
                        setModalState(state, {
                            title: state === 'success'
                                ? (action === 'clockout' ? 'Clocked Out Successfully' : 'Clocked In Successfully')
                                : 'Unable to update attendance',
                            subtitle: message
                        });
                    })
                    .catch(function () {
                        setModalState('error', {
                            title: 'Unable to update attendance',
                            subtitle: 'Unable to process attendance right now.'
                        });
                    })
                    .finally(function () {
                        resetButton(button);
                    });
            };

            if (manualClockConfirmBtn) {
                manualClockConfirmBtn.addEventListener('click', function () {
                    if (!pendingRequest.action || !pendingRequest.member || !pendingRequest.button) {
                        return;
                    }
                    submitManualClock(pendingRequest.action, pendingRequest.member, pendingRequest.button);
                });
            }

            if (manualClockModalEl) {
                manualClockModalEl.addEventListener('hidden.bs.modal', function () {
                    pendingRequest.action = null;
                    pendingRequest.member = null;
                    pendingRequest.button = null;
                    if (manualClockConfirmBtn) {
                        manualClockConfirmBtn.classList.add('d-none');
                    }
                    if (manualClockCloseBtn) {
                        manualClockCloseBtn.textContent = 'Close';
                    }
                });
            }

            manualClockButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetButton = this;
                    const action = targetButton.dataset.action;
                    const member = normalizeMemberData(targetButton);

                    pendingRequest.action = action;
                    pendingRequest.member = member;
                    pendingRequest.button = targetButton;

                    updateMemberCard(member);

                    if (action === 'clockout') {
                        setModalState('confirm', {
                            title: 'Clock Out',
                            subtitle: 'Please confirm clocking out the member.'
                        });
                        showManualClockModal();
                        return;
                    }

                    submitManualClock(action, member, targetButton);
                });
            });

            if (!form) {
                return;
            }
            const statusInput = document.getElementById('member-status-filter');
            const chipButtons = form.querySelectorAll('.membership-chip');
            const rangeButtons = form.querySelectorAll('.range-chip');
            const startInput = document.getElementById('start-date');
            const endInput = document.getElementById('end-date');

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
                form.submit();
            }

            chipButtons.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    const selectedStatus = this.dataset.status;
                    statusInput.value = selectedStatus;

                    chipButtons.forEach(function (btn) {
                        btn.classList.remove('btn-dark', 'text-white', 'shadow-sm');
                        if (!btn.classList.contains('btn-outline-secondary')) {
                            btn.classList.add('btn-outline-secondary');
                        }
                    });

                    this.classList.remove('btn-outline-secondary');
                    this.classList.add('btn-dark', 'text-white', 'shadow-sm');

                    form.submit();
                });
            });

            rangeButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    applyRange(this.dataset.range);
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const renewButtons = document.querySelectorAll('.renew-membership-button');
            const renewModalEl = document.getElementById('renewMembershipModal');

            if (!renewModalEl || !renewButtons.length) {
                return;
            }

            const renewForm = renewModalEl.querySelector('#renewMembershipForm');
            const actionBase = renewForm ? renewForm.getAttribute('data-action-base') : '';
            const nameEl = renewModalEl.querySelector('#renewMemberName');
            const emailEl = renewModalEl.querySelector('#renewMemberEmail');
            const phoneEl = renewModalEl.querySelector('#renewMemberPhone');
            const codeEl = renewModalEl.querySelector('#renewMemberCode');
            const statusBadgeEl = renewModalEl.querySelector('#renewMembershipStatusBadge');
            const statusTextEl = renewModalEl.querySelector('#renewMembershipStatusText');
            const membershipNameEl = renewModalEl.querySelector('#renewMembershipName');
            const membershipExpiresEl = renewModalEl.querySelector('#renewMembershipExpires');
            const selectEl = renewModalEl.querySelector('#renewMembershipSelect');
            const noticeEl = renewModalEl.querySelector('#renewMembershipNotice');
            const submitBtn = renewModalEl.querySelector('#renewMembershipSubmitBtn');
            const loaderEl = renewModalEl.querySelector('#renewMembershipLoader');

            const hasSelectableMemberships = function () {
                if (!selectEl) {
                    return false;
                }
                return Array.from(selectEl.options).some((option) => option.value);
            };

            const setStatusBadge = function (active) {
                if (!statusBadgeEl) {
                    return;
                }
                statusBadgeEl.textContent = active ? 'Active' : 'Inactive';
                statusBadgeEl.classList.remove('bg-success', 'bg-secondary');
                statusBadgeEl.classList.add(active ? 'bg-success' : 'bg-secondary');
            };

            const setNotice = function (message) {
                if (!noticeEl) {
                    return;
                }
                if (message) {
                    noticeEl.textContent = message;
                    noticeEl.classList.remove('d-none');
                } else {
                    noticeEl.textContent = '';
                    noticeEl.classList.add('d-none');
                }
            };

            const setSelectValue = function (value) {
                if (!selectEl) {
                    return;
                }
                const options = Array.from(selectEl.options).filter((option) => option.value);
                if (value && options.some((option) => option.value === value)) {
                    selectEl.value = value;
                } else if (options.length) {
                    selectEl.value = options[0].value;
                } else {
                    selectEl.value = '';
                }
            };

            renewButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const data = button.dataset;
                    const memberId = data.memberId;

                    if (renewForm && actionBase && memberId) {
                        renewForm.action = `${actionBase}/${memberId}`;
                    }

                    if (nameEl) nameEl.textContent = data.memberName || '—';
                    if (emailEl) emailEl.textContent = data.memberEmail || '—';
                    if (phoneEl) phoneEl.textContent = data.memberPhone || '—';
                    if (codeEl) {
                        const rawCode = data.memberCode || '';
                        codeEl.textContent = rawCode ? (rawCode.startsWith('#') ? rawCode : `#${rawCode}`) : '—';
                    }
                    if (statusTextEl) statusTextEl.textContent = data.membershipStatus || '—';
                    if (membershipNameEl) membershipNameEl.textContent = data.membershipName || 'No Membership';
                    if (membershipExpiresEl) membershipExpiresEl.textContent = data.membershipExpires || '—';

                    const isActive = data.membershipActive === '1';
                    setStatusBadge(isActive);
                    setSelectValue(data.membershipId || '');

                    const selectable = hasSelectableMemberships();
                    const canRenew = !isActive && selectable;

                    if (selectEl) {
                        selectEl.disabled = !canRenew;
                    }
                    if (submitBtn) {
                        submitBtn.disabled = !canRenew;
                    }

                    if (isActive) {
                        setNotice('Membership is active. Renewals are available after expiration.');
                    } else if (!selectable) {
                        setNotice('No active membership plans are available to renew.');
                    } else {
                        setNotice('');
                    }

                    if (loaderEl) {
                        loaderEl.classList.add('d-none');
                    }
                });
            });

            renewForm?.addEventListener('submit', function () {
                if (submitBtn) {
                    submitBtn.disabled = true;
                }
                if (loaderEl) {
                    loaderEl.classList.remove('d-none');
                }
            });

            renewModalEl.addEventListener('hidden.bs.modal', function () {
                if (loaderEl) {
                    loaderEl.classList.add('d-none');
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
                if (selectEl) {
                    selectEl.disabled = false;
                }
                setNotice('');
                if (renewForm) {
                    renewForm.reset();
                }
            });
        });
    </script>
@endsection
