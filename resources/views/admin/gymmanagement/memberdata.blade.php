@extends('layouts.admin')
@section('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('title', 'Members Data')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $showArchived = request()->boolean('show_archived');
                $printMeta = [
                    'title' => $showArchived ? 'Archived members' : 'Member directory',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => request('name'),
                        'search_column' => request('search_column'),
                        'membership_status' => request('membership_status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                    ],
                ];
            @endphp
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Members Data</h2></div>
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
                          <input type="hidden" name="search_column" value="{{ request('search_column') }}">
                          <input type="hidden" name="membership_status" value="{{ request('membership_status', 'all') }}">
                      
                          <button
                            class="btn btn-md btn-danger ms-2"
                            type="submit"
                            id="print-submit-button"
                            data-print='@json($printMeta)'
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

                    function text(cell) {
                        return (cell ? cell.textContent : '').replace(/\s+/g, ' ').trim();
                    }

                    function collectTableItems() {
                        const activeTable = document.getElementById('member-table');
                        const archivedTable = document.getElementById('archived-member-table');
                        const table = activeTable || archivedTable;
                        if (!table) return [];

                        const isActive = !!activeTable;
                        const rows = table.querySelectorAll('tbody tr');
                        const items = [];

                        rows.forEach((row) => {
                            const cells = row.querySelectorAll('td');
                            if (!cells.length || (cells[0].hasAttribute('colspan'))) return;

                            if (isActive && cells.length >= 9) {
                                const code = text(cells[0]);
                                const membership = text(cells[1]);
                                const expires = text(cells[2]);
                                const name = text(cells[3]);
                                const phone = text(cells[4]);
                                const email = text(cells[5]);
                                const created = text(cells[6]);
                                const updated = text(cells[7]);
                                const createdBy = text(cells[8]);
                                const membershipStatus = /no membership/i.test(membership) ? 'No membership' : 'Active membership';

                                items.push({
                                    code,
                                    membership,
                                    membership_status: membershipStatus,
                                    membership_expires: expires || 'No expiration',
                                    name,
                                    phone,
                                    email,
                                    created,
                                    updated,
                                    created_by: createdBy || '—',
                                });
                            } else if (!isActive && cells.length >= 8) {
                                const code = text(cells[0]);
                                const membership = text(cells[1]);
                                const expires = text(cells[2]);
                                const name = text(cells[3]);
                                const phone = text(cells[4]);
                                const email = text(cells[5]);
                                const created = text(cells[6]);
                                const updated = text(cells[7]);
                                const membershipStatus = /no membership/i.test(membership) ? 'No membership' : 'Active membership';

                                items.push({
                                    code,
                                    membership,
                                    membership_status: membershipStatus,
                                    membership_expires: expires || 'No expiration',
                                    name,
                                    phone,
                                    email,
                                    created,
                                    updated,
                                    created_by: '—',
                                });
                            }
                        });

                        return items;
                    }

                    function buildFilters(filters) {
                        const chips = [];
                        if (filters.show_archived) chips.push('Archived view');
                        if (filters.membership_status && filters.membership_status !== 'all') chips.push(`Membership: ${filters.membership_status}`);
                        if (filters.search) {
                            chips.push(
                                `Search: ${filters.search}${filters.search_column ? ` (${filters.search_column})` : ''}`
                            );
                        }
                        if (filters.start || filters.end) {
                            chips.push(`Date: ${filters.start || '—'} → ${filters.end || '—'}`);
                        }
                        return chips.map((chip) => `<span class="pill">${chip}</span>`).join('') || '<span class="muted">No filters applied</span>';
                    }

                    function buildRows(items) {
                        return items.map((item) => {
                            return `
                                <tr>
                                    <td>${item.code || '—'}</td>
                                    <td>
                                        <div class="fw">${item.name || '—'}</div>
                                        <div class="muted">${item.email || ''}</div>
                                        <div class="muted">${item.phone || ''}</div>
                                    </td>
                                    <td>
                                        <div>${item.membership || 'No membership'}</div>
                                        <div class="muted">${item.membership_status || ''}</div>
                                        <div class="muted">Expires: ${item.membership_expires || '—'}</div>
                                    </td>
                                    <td>
                                        <div>${item.created || ''}</div>
                                        <div class="muted">${item.updated || ''}</div>
                                        <div class="muted">Created by: ${item.created_by || '—'}</div>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    }

                    function renderPrintWindow(payload) {
                        const items = collectTableItems();
                        const filters = payload.filters || {};
                        payload.count = items.length;
                        const rows = buildRows(items);
                        const html = `
                            <!doctype html>
                            <html>
                                <head>
                                    <title>${payload.title || 'Member directory'}</title>
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
                                                <h1 class="title">${payload.title || 'Member directory'}</h1>
                                                <div class="muted">Generated ${payload.generated_at || ''}</div>
                                                <div class="muted">Showing ${payload.count || 0} record(s)</div>
                                            </div>
                                        </div>
                                        <div class="pill-row">${buildFilters(filters)}</div>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Member</th>
                                                    <th>Membership</th>
                                                    <th>Audit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${rows || '<tr><td colspan="4" style="text-align:center; padding:16px;">No members available for this view.</td></tr>'}
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
                $advancedFiltersOpen = request()->filled('search_column') || request()->filled('start_date') || request()->filled('end_date');
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
                                                    <label for="search-column" class="form-label text-muted text-uppercase small mb-1">Search by</label>
                                                    <select id="search-column" name="search_column" class="form-select rounded-3">
                                                        <option value="" disabled {{ request('search_column') ? '' : 'selected' }}>Select Option</option>
                                                        <option value="id" {{ request('search_column') == 'id' ? 'selected' : '' }}>ID</option>
                                                        <option value="name" {{ request('search_column') == 'name' ? 'selected' : '' }}>Name</option>
                                                        <option value="phone_number" {{ request('search_column') == 'phone_number' ? 'selected' : '' }}>Phone Number</option>
                                                        <option value="email" {{ request('search_column') == 'email' ? 'selected' : '' }}>Email</option>
                                                        <option value="created_at" {{ request('search_column') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                                        <option value="updated_at" {{ request('search_column') == 'updated_at' ? 'selected' : '' }}>Updated Date</option>
                                                        <option value="created_by" {{ request('search_column') == 'created_by' ? 'selected' : '' }}>Created By</option>
                                                    </select>
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
                                            <th class="sortable" data-column="created_by">Created By <i class="fa fa-sort"></i></th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($gym_members as $item)
                                        @php
                                            $latestMembershipPayment = $item->membershipPayments()
                                                ->where('isapproved', 1)
                                                ->where('expiration_at', '>=', $current_time)
                                                ->with('membership')
                                                ->orderBy('created_at', 'desc')
                                                ->first();

                                            $membershipName = optional(optional($latestMembershipPayment)->membership)->name ?? 'No Membership';
                                            $expirationAt   = optional($latestMembershipPayment)->expiration_at ?? 'No Expiration Date';

                                            // UPDATED START: compute boolean for client-side filtering
                                            $hasMembership = $membershipName !== 'No Membership';
                                            // UPDATED END
                                        @endphp

                                        {{-- UPDATED START: mark each row for filtering --}}
                                        <tr data-has-membership="{{ $hasMembership ? '1' : '0' }}">
                                        {{-- UPDATED END --}}
                                            <td>{{ $item->id }}</td>
                                            <td>{{ $item->user_code }}</td>

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
                                            <td>{{ $item->created_by }}</td>
                                            <td>
                                                <div class="d-flex flex-wrap align-items-center gap-2">
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="Manual attendance actions">
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-success manual-clock-button"
                                                            data-email="{{ $item->email }}"
                                                            data-name="{{ $item->first_name }} {{ $item->last_name }}"
                                                            data-action="clockin"
                                                        >
                                                            <i class="fa-regular fa-clock me-1"></i>Clock In
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-secondary manual-clock-button"
                                                            data-email="{{ $item->email }}"
                                                            data-name="{{ $item->first_name }} {{ $item->last_name }}"
                                                            data-action="clockout"
                                                        >
                                                            <i class="fa-solid fa-right-from-bracket me-1"></i>Clock Out
                                                        </button>
                                                    </div>

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
                                                                    title="Delete"
                                                                    style="background: none; border: none; padding: 0; cursor: pointer;"
                                                                >
                                                                    <i class="fa-solid fa-trash text-danger"></i>
                                                                </button>
                                                            @endif
                                                            {{-- UPDATED END --}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- Delete modal (unchanged aside from minor null-safe id lookups) --}}
                                        <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="rejectModalLabel">Are you sure you want to delete ({{ $item->email }})?</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('admin.gym-management.members.delete') }}" method="POST" id="main-form-{{ $item->id }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="id" value="{{ $item->id }}">
                                                        <div class="modal-body">
                                                            <div class="input-group mt-3">
                                                                <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <!--<button type="submit" class="btn btn-danger">Submit</button>-->
                                                            <button class="btn btn-danger" type="submit" id="submitButton-{{ $item->id }}">
                                                                <span id="loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                Submit
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
                                            <th>ID</th>
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
                                                    ->with('membership')
                                                    ->orderBy('created_at', 'desc')
                                                    ->first();

                                                $membershipName = optional(optional($latestMembershipPayment)->membership)->name ?? 'No Membership';
                                                $expirationAt   = optional($latestMembershipPayment)->expiration_at ?? 'No Expiration Date';
                                            @endphp
                                            <tr>
                                                <td>{{ $archive->id }}</td>
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

    <div class="modal fade" id="manualClockModal" tabindex="-1" aria-labelledby="manualClockModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manualClockModalLabel">Manual attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="manualClockModalMessage" class="mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('member-filter-form');
            const manualClockButtons = document.querySelectorAll('.manual-clock-button');
            const manualClockModalEl = document.getElementById('manualClockModal');
            const manualClockModalMessageEl = document.getElementById('manualClockModalMessage');
            const csrfMeta = document.querySelector("meta[name='csrf-token']");
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            const feedbackModalEl = document.getElementById('actionFeedbackModal');
            if (feedbackModalEl && typeof bootstrap !== 'undefined') {
                const feedbackModal = new bootstrap.Modal(feedbackModalEl);
                feedbackModal.show();
            }

            function showManualClockMessage(message) {
                if (manualClockModalMessageEl) {
                    manualClockModalMessageEl.textContent = message;
                }

                if (manualClockModalEl && typeof bootstrap !== 'undefined') {
                    const manualClockModal = new bootstrap.Modal(manualClockModalEl);
                    manualClockModal.show();
                } else {
                    alert(message);
                }
            }

            manualClockButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetButton = this;
                    const email = targetButton.dataset.email;
                    const action = targetButton.dataset.action;
                    const name = targetButton.dataset.name || 'Member';

                    if (!csrfToken || !email || !action) {
                        showManualClockMessage('Unable to process attendance right now.');
                        return;
                    }

                    const originalHtml = targetButton.innerHTML;
                    const loadingText = action === 'clockout' ? 'Clocking out...' : 'Clocking in...';
                    targetButton.disabled = true;
                    targetButton.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${loadingText}`;

                    fetch("{{ route('admin.staff-account-management.attendances.scanner2.fetch') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ result: email, action: action })
                    })
                        .then(function (response) {
                            return response.json().catch(function () {
                                return { data: 'Unable to process attendance right now.' };
                            });
                        })
                        .then(function (data) {
                            const message = data && data.data ? data.data : `${name}'s attendance was updated.`;
                            showManualClockMessage(message);
                        })
                        .catch(function () {
                            showManualClockMessage('Unable to process attendance right now.');
                        })
                        .finally(function () {
                            targetButton.disabled = false;
                            targetButton.innerHTML = originalHtml;
                        });
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
@endsection
