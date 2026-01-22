@extends('layouts.admin')

@section('styles')
    <script src="https://cdn.jsdelivr.net/gh/schmich/instascan-builds@master/instascan.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Attendances')

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
        .manual-clock-warning {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 14px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        }
        .manual-clock-warning-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(245, 158, 11, 0.15);
            color: #b45309;
            flex: 0 0 auto;
            font-size: 1rem;
        }
        .manual-clock-warning-title {
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 2px;
            color: #7c2d12;
        }
        .manual-clock-warning-subtitle {
            font-size: 0.75rem;
            color: #9a3412;
            margin-bottom: 0;
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
                $printSource = $showArchived ? $archivedData : $data;
                $printAllSource = $showArchived ? ($printAllArchived ?? collect()) : ($printAllActive ?? collect());
                $printAttendances = collect($printSource->items())->map(function ($item) {
                    $clockIn = $item->clockin_at ? \Carbon\Carbon::parse($item->clockin_at) : null;
                    $clockOut = $item->clockout_at ? \Carbon\Carbon::parse($item->clockout_at) : null;
                    $name = trim((optional($item->user)->first_name ?? '') . ' ' . (optional($item->user)->last_name ?? ''));
                    $role = optional(optional($item->user)->role)->name;
                    $statusLabel = $clockOut ? 'Completed' : 'Pending clock-out';

                    return [
                        'id' => $item->id,
                        'role' => $role ?: '—',
                        'name' => $name ?: '—',
                        'user_code' => optional($item->user)->user_code ?? null,
                        'clock_in' => $clockIn ? $clockIn->format('M j, Y g:i A') : '—',
                        'clock_out' => $clockOut ? $clockOut->format('M j, Y g:i A') : '—',
                        'status' => $statusLabel,
                        'created_at' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('M j, Y g:i A') : '',
                        'updated_at' => $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->format('M j, Y g:i A') : '',
                    ];
                })->values();

                $printAllAttendances = collect($printAllSource)->map(function ($item) {
                    $clockIn = $item->clockin_at ? \Carbon\Carbon::parse($item->clockin_at) : null;
                    $clockOut = $item->clockout_at ? \Carbon\Carbon::parse($item->clockout_at) : null;
                    $name = trim((optional($item->user)->first_name ?? '') . ' ' . (optional($item->user)->last_name ?? ''));
                    $role = optional(optional($item->user)->role)->name;
                    $statusLabel = $clockOut ? 'Completed' : 'Pending clock-out';

                    return [
                        'id' => $item->id,
                        'role' => $role ?: '—',
                        'name' => $name ?: '—',
                        'user_code' => optional($item->user)->user_code ?? null,
                        'clock_in' => $clockIn ? $clockIn->format('M j, Y g:i A') : '—',
                        'clock_out' => $clockOut ? $clockOut->format('M j, Y g:i A') : '—',
                        'status' => $statusLabel,
                        'created_at' => $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('M j, Y g:i A') : '',
                        'updated_at' => $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->format('M j, Y g:i A') : '',
                    ];
                })->values();

                $printPayload = [
                    'title' => $showArchived ? 'Archived attendances' : 'Attendance log',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => request('name'),
                        'status' => request('status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                    ],
                    'count' => $printAttendances->count(),
                    'items' => $printAttendances,
                ];

                $printAllPayload = [
                    'title' => $showArchived ? 'Archived attendances (all pages)' : 'Attendance log (all pages)',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => request('name'),
                        'status' => request('status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                        'scope' => 'all',
                    ],
                    'count' => $printAllAttendances->count(),
                    'items' => $printAllAttendances,
                ];
            @endphp
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Attendances</h2></div>
                <div class="d-flex align-items-center">
                    <a class="btn btn-danger" href="#attendance-scanner-card"><i class="fa-solid fa-qrcode"></i>&nbsp;&nbsp;&nbsp;Scanner</a>
                    <form action="{{ route('admin.staff-account-management.attendances.print') }}" method="POST" id="print-form">
                        @csrf
                        <input type="hidden" name="created_start" value="{{ request('start_date') }}">
                        <input type="hidden" name="created_end" value="{{ request('end_date') }}">
                        <input type="hidden" name="name" value="{{ request('name') }}">
                        <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                        <button
                            class="btn btn-danger ms-2"
                            type="submit"
                            id="print-submit-button"
                            data-print='@json($printPayload)'
                            data-print-all='@json($printAllPayload)'
                            aria-label="Open printable/PDF view of filtered attendances"
                        >
                            <i class="fa-solid fa-print"></i>
                            <span id="print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.staff-account-management.attendances', request()->except(['show_archived', 'page', 'archive_page'])) }}"
                        >
                            <i class="fa-solid fa-rotate-left"></i>&nbsp;&nbsp;Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.staff-account-management.attendances', array_merge(request()->except(['page', 'archive_page']), ['show_archived' => 1])) }}"
                        >
                            <i class="fa-solid fa-box-archive"></i>&nbsp;&nbsp;View archived
                        </a>
                    @endif
                </div>
            </div>

            <div class="col-12 mt-4" id="attendance-scanner-card">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">QR Scanner</span>
                                <h4 class="fw-semibold mb-1">Scan attendance</h4>
                                <p class="text-muted mb-0">Enable the camera to scan staff QR codes without leaving this page.</p>
                            </div>
                            <div class="text-end">
                                <div class="btn-group" role="group" aria-label="Camera controls">
                                    <button class="btn btn-dark" type="button" id="enable-camera-btn" disabled>
                                        <i class="fa-solid fa-play"></i>&nbsp;&nbsp;Enable camera
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" id="disable-camera-btn" disabled>
                                        <i class="fa-solid fa-stop"></i>&nbsp;&nbsp;Disable camera
                                    </button>
                                </div>
                                <small class="d-block mt-2 text-muted" id="camera-status-text">Camera not ready</small>
                            </div>
                        </div>
                        <div id="attendance-scanner-wrapper" class="ratio ratio-16x9 border rounded-4 overflow-hidden bg-black mt-3 d-none">
                            <video id="attendance-scanner" class="w-100 h-100" playsinline></video>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $statusFilter = request('status', 'all');
                $statusTallies = $statusTallies ?? [];
                $statusOptions = [
                    'all' => [
                        'label' => 'All records',
                        'count' => $statusTallies['all'] ?? null,
                    ],
                    'open' => [
                        'label' => 'Pending clock-out',
                        'count' => $statusTallies['open'] ?? null,
                    ],
                    'completed' => [
                        'label' => 'Completed',
                        'count' => $statusTallies['completed'] ?? null,
                    ],
                ];
                $advancedFiltersOpen = request()->filled('start_date') || request()->filled('end_date');
            @endphp

            <div class="col-12 mb-20 mt-3">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Overview</span>
                                <h4 class="fw-semibold mb-1">Attendance log</h4>
                                <p class="text-muted mb-0">Highlight open sessions or drill into specific days with the filters below.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">
                                    @if ($showArchived)
                                        Showing {{ $archivedData->total() }} archived attendances
                                    @else
                                        Showing {{ $data->total() }} results
                                    @endif
                                </span>
                            </div>
                        </div>

                        <form action="{{ route('admin.staff-account-management.attendances') }}" method="GET" id="attendance-filter-form" class="mt-4">
                            <input type="hidden" name="status" id="attendance-status-filter" value="{{ $statusFilter }}">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @foreach ($statusOptions as $key => $option)
                                        <button
                                            type="button"
                                            class="status-chip btn btn-sm rounded-pill px-3 {{ $statusFilter === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                            data-status="{{ $key }}"
                                        >
                                            {{ $option['label'] }}
                                            @if(!is_null($option['count']))
                                                <span class="badge bg-transparent {{ $statusFilter === $key ? 'text-white' : 'text-dark' }} fw-semibold ms-2">{{ $option['count'] }}</span>
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
                                                placeholder="Search name, role, code, email"
                                                value="{{ request('name') }}"
                                                aria-label="Search attendances"
                                            />
                                        </div>
                                    </div>

                                    <a
                                        href="{{ $showArchived ? route('admin.staff-account-management.attendances', ['show_archived' => 1]) : route('admin.staff-account-management.attendances') }}"
                                        class="btn btn-link text-decoration-none text-muted px-0"
                                    >
                                        Reset
                                    </a>

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#attendanceFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="attendanceFiltersModal" tabindex="-1" aria-labelledby="attendanceFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="attendanceFiltersModalLabel">Advanced filters</h5>
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
                                <table class="table table-hover">
                                   <thead class="table-light">
                                       <tr>
                                           <th class="sortable" data-column="id"># <i class="fa fa-sort"></i></th>
                                           <th class="sortable" data-column="role">Role <i class="fa fa-sort"></i></th>
                                           <th class="sortable" data-column="member_name">Member Name <i class="fa fa-sort"></i></th>
                                            <th>User Code</th>
                                           <th class="sortable" data-column="clock_in_date">Clock In Date <i class="fa fa-sort"></i></th>
                                           <th class="sortable" data-column="clock_out_date">Clock Out Date <i class="fa fa-sort"></i></th>
                                           <th>Actions</th>
                                       </tr>
                                   </thead>
                                   <tbody id="table-body">
                                       @foreach($data as $item)
                                           <tr>
                                               <td>{{ $item->id }}</td>
                                               <td>{{ $item->user->role->name }}</td>
                                               <td>{{ $item->user->first_name }} {{ $item->user->last_name }}</td>
                                                <td>
                                                    <span class="text-muted small">{{ $item->user->user_code ?? '—' }}</span>
                                                </td>
                                               <td>
                                                   @if ($item->clockin_at)
                                                       {{ \Carbon\Carbon::parse($item->clockin_at)->format('F j, Y g:iA') }}
                                                   @else
                                                       —
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($item->clockout_at)
                                                        {{ \Carbon\Carbon::parse($item->clockout_at)->format('F j, Y g:iA') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        <div class="action-button">
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
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel-{{ $item->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0 shadow rounded-4">
                                                        <div class="modal-header border-0 pb-0">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="badge bg-danger bg-opacity-10 text-danger rounded-circle p-3">
                                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                                </div>
                                                                <div>
                                                                    <p class="text-uppercase text-muted small mb-1">Archive attendance</p>
                                                                    <h5 class="fw-semibold mb-0" id="deleteModalLabel-{{ $item->id }}">
                                                                        Attendance #{{ $item->id }}
                                                                    </h5>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.staff-account-management.attendances.delete') }}" method="POST" id="main-form-{{ $item->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                                            <div class="modal-body pt-3">
                                                                <div class="alert alert-danger bg-opacity-10 text-danger border-0 rounded-3">
                                                                    Archiving will move this attendance record to the archived list. You can restore it later if needed.
                                                                </div>
                                                                <label class="form-label fw-semibold mt-2">Confirm with your password</label>
                                                                <div class="input-group">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0 pt-0">
                                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                                <button class="btn btn-danger" type="submit" id="submitButton-{{ $item->id }}">
                                                                    <span id="loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Archive attendance
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                                document.getElementById('main-form-{{ $item->id }}')?.addEventListener('submit', function (e) {
                                                    const submitButton = document.getElementById('submitButton-{{ $item->id }}');
                                                    const loader = document.getElementById('loader-{{ $item->id }}');

                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                        @endforeach
                                    </tbody>
                                </table>
                                {{ $data->links() }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if ($showArchived)
    <div class="box mt-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <h4 class="fw-semibold mb-0">Archived Attendance</h4>
                    <span class="text-muted small">Showing {{ $archivedData->total() }} archived</span>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Role</th>
                                        <th>Member Name</th>
                                        <th>User Code</th>
                                        <th>Clock In Date</th>
                                        <th>Clock Out Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                        <tbody>
                                @forelse ($archivedData as $archive)
                                    <tr>
                                        <td>{{ $archive->id }}</td>
                                        <td>{{ optional(optional($archive->user)->role)->name }}</td>
                                        <td>{{ optional($archive->user)->first_name }} {{ optional($archive->user)->last_name }}</td>
                                        <td>
                                            <span class="text-muted small">{{ $archive->user->user_code ?? '—' }}</span>
                                        </td>
                                        <td>
                                            @if ($archive->clockin_at)
                                                {{ \Carbon\Carbon::parse($archive->clockin_at)->format('F j, Y g:iA') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($archive->clockout_at)
                                            {{ \Carbon\Carbon::parse($archive->clockout_at)->format('F j, Y g:iA') }}
                                        @else
                                            —
                                        @endif
                                    </td>
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
                                                <h5 class="modal-title" id="archiveRestoreModalLabel-{{ $archive->id }}">Restore attendance #{{ $archive->id }}?</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('admin.staff-account-management.attendances.restore') }}" method="POST" id="archive-restore-modal-form-{{ $archive->id }}">
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
                                                <h5 class="modal-title" id="archiveDeleteModalLabel-{{ $archive->id }}">Delete attendance #{{ $archive->id }} permanently?</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('admin.staff-account-management.attendances.delete') }}" method="POST" id="archive-delete-modal-form-{{ $archive->id }}">
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
                                    document.getElementById('archive-restore-modal-form-{{ $archive->id }}')?.addEventListener('submit', function (e) {
                                        const submitButton = document.getElementById('archive-restore-modal-submit-button-{{ $archive->id }}');
                                        const loader = document.getElementById('archive-restore-modal-loader-{{ $archive->id }}');

                                        submitButton.disabled = true;
                                        loader.classList.remove('d-none');
                                    });
                                </script>
                                <script>
                                    document.getElementById('archive-delete-modal-form-{{ $archive->id }}')?.addEventListener('submit', function (e) {
                                        const submitButton = document.getElementById('archive-delete-modal-submit-button-{{ $archive->id }}');
                                        const loader = document.getElementById('archive-delete-modal-loader-{{ $archive->id }}');

                                        submitButton.disabled = true;
                                        loader.classList.remove('d-none');
                                    });
                                </script>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No archived attendance records found.</td>
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

    <div
        class="modal fade manual-clock-modal"
        id="scanClockModal"
        tabindex="-1"
        aria-labelledby="scanClockModalLabel"
        aria-hidden="true"
        data-default-avatar="{{ asset('assets/images/profile-45x45.png') }}"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header pb-0">
                    <h5 class="modal-title" id="scanClockModalLabel">Scan attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="manual-clock-hero">
                        <div class="manual-clock-icon manual-clock-icon--success" id="scanClockIcon">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <h4 class="manual-clock-title" id="scanClockTitle">Attendance updated</h4>
                        <p class="manual-clock-subtitle" id="scanClockSubtitle">Ready to scan.</p>
                    </div>
                    <div class="manual-clock-member-card">
                        <img src="{{ asset('assets/images/profile-45x45.png') }}" alt="Member photo" class="manual-clock-avatar" id="scanClockAvatar">
                        <div class="manual-clock-member-info">
                            <div class="manual-clock-member-name" id="scanClockMemberName">Member</div>
                            <div class="manual-clock-member-meta" id="scanClockMemberEmail">member@email.com</div>
                            <div class="manual-clock-member-meta" id="scanClockMemberPhone">No phone</div>
                            <div class="manual-clock-chips">
                                <span class="manual-clock-chip" id="scanClockMemberCode">#---</span>
                                <span class="manual-clock-chip manual-clock-chip--inactive" id="scanClockMemberMembership">No Membership</span>
                            </div>
                        </div>
                    </div>
                    <div class="manual-clock-warning d-none" id="scanClockWarning">
                        <div class="manual-clock-warning-icon">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <div class="manual-clock-warning-title" id="scanClockWarningTitle">
                                Warning: Your membership is about to expire soon.
                            </div>
                            <div class="manual-clock-warning-subtitle" id="scanClockWarningSubtitle">
                                Please renew your membership to avoid any interruptions.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const scannerWrapper = document.getElementById('attendance-scanner-wrapper');
            const scannerVideo = document.getElementById('attendance-scanner');
            const enableCameraBtn = document.getElementById('enable-camera-btn');
            const disableCameraBtn = document.getElementById('disable-camera-btn');
            const cameraStatusText = document.getElementById('camera-status-text');
            const scanClockModalEl = document.getElementById('scanClockModal');
            const scanClockIconEl = scanClockModalEl ? scanClockModalEl.querySelector('#scanClockIcon') : null;
            const scanClockTitleEl = scanClockModalEl ? scanClockModalEl.querySelector('#scanClockTitle') : null;
            const scanClockSubtitleEl = scanClockModalEl ? scanClockModalEl.querySelector('#scanClockSubtitle') : null;
            const scanClockAvatarEl = scanClockModalEl ? scanClockModalEl.querySelector('#scanClockAvatar') : null;
            const scanClockMemberNameEl = scanClockModalEl ? scanClockModalEl.querySelector('#scanClockMemberName') : null;
            const scanClockMemberEmailEl = scanClockModalEl ? scanClockModalEl.querySelector('#scanClockMemberEmail') : null;
            const scanClockMemberPhoneEl = scanClockModalEl ? scanClockModalEl.querySelector('#scanClockMemberPhone') : null;
            const scanClockMemberCodeEl = scanClockModalEl ? scanClockModalEl.querySelector('#scanClockMemberCode') : null;
            const scanClockMemberMembershipEl = scanClockModalEl ? scanClockModalEl.querySelector('#scanClockMemberMembership') : null;
            const scanClockWarningEl = scanClockModalEl ? scanClockModalEl.querySelector('#scanClockWarning') : null;
            const scanClockWarningTitleEl = scanClockModalEl ? scanClockModalEl.querySelector('#scanClockWarningTitle') : null;
            const scanClockWarningSubtitleEl = scanClockModalEl ? scanClockModalEl.querySelector('#scanClockWarningSubtitle') : null;
            const scanClockModal = scanClockModalEl && typeof bootstrap !== 'undefined'
                ? new bootstrap.Modal(scanClockModalEl)
                : null;
            const defaultAvatar = scanClockModalEl ? scanClockModalEl.getAttribute('data-default-avatar') : '';

            let scannerInstance = null;
            let availableCameras = [];
            let activeCameraIndex = 0;
            let isCameraRunning = false;

            function toggleScannerVisibility(show) {
                if (!scannerWrapper) {
                    return;
                }

                scannerWrapper.classList.toggle('d-none', !show);
            }

            function setCameraStatus(message, variant = 'muted') {
                if (!cameraStatusText) {
                    return;
                }

                cameraStatusText.textContent = message;
                cameraStatusText.classList.remove('text-muted', 'text-success', 'text-danger');

                const variantClassMap = {
                    muted: 'text-muted',
                    success: 'text-success',
                    danger: 'text-danger',
                };

                cameraStatusText.classList.add(variantClassMap[variant] ?? 'text-muted');
            }

            const scanIconMap = {
                clockin: { icon: 'fa-solid fa-circle-check', className: 'manual-clock-icon--success' },
                clockout: { icon: 'fa-regular fa-clock', className: 'manual-clock-icon--danger' },
                error: { icon: 'fa-solid fa-triangle-exclamation', className: 'manual-clock-icon--warning' },
                loading: { icon: 'fa-solid fa-spinner fa-spin', className: 'manual-clock-icon--loading' },
                success: { icon: 'fa-solid fa-circle-check', className: 'manual-clock-icon--success' },
            };

            const parseDaysRemaining = function (value) {
                if (value === undefined || value === null || value === '') {
                    return null;
                }
                const parsed = parseInt(value, 10);
                return Number.isFinite(parsed) ? parsed : null;
            };

            const buildWarningCopy = function (daysRemaining) {
                if (daysRemaining === 0) {
                    return {
                        title: 'Warning: Your membership expires today.',
                        subtitle: 'Please renew your membership to avoid any interruptions.'
                    };
                }
                const dayLabel = daysRemaining === 1 ? '1 day' : `${daysRemaining} days`;
                return {
                    title: `Warning: Your membership is about to expire in ${dayLabel}.`,
                    subtitle: 'Please renew your membership to avoid any interruptions.'
                };
            };

            const updateScanWarning = function (member, status) {
                if (!scanClockWarningEl) {
                    return;
                }
                const daysRemaining = member && typeof member.membershipDaysRemaining === 'number'
                    ? member.membershipDaysRemaining
                    : null;
                const shouldShow = status === 'clockin' && typeof daysRemaining === 'number' && daysRemaining <= 7;

                if (!shouldShow) {
                    scanClockWarningEl.classList.add('d-none');
                    return;
                }

                const copy = buildWarningCopy(daysRemaining);
                if (scanClockWarningTitleEl) scanClockWarningTitleEl.textContent = copy.title;
                if (scanClockWarningSubtitleEl) scanClockWarningSubtitleEl.textContent = copy.subtitle;
                scanClockWarningEl.classList.remove('d-none');
            };

            const normalizeScanUser = function (payload) {
                return {
                    name: payload?.name || 'Member',
                    email: payload?.email || 'No email',
                    phone: payload?.phone || 'No phone',
                    code: payload?.code || '#---',
                    membership: payload?.membership || 'No Membership',
                    membershipActive: payload?.membership_active === true,
                    membershipDaysRemaining: parseDaysRemaining(payload?.membership_days_remaining),
                    avatar: payload?.avatar || defaultAvatar,
                };
            };

            const updateScanMemberCard = function (member) {
                if (scanClockAvatarEl) {
                    scanClockAvatarEl.src = member.avatar || defaultAvatar || scanClockAvatarEl.src;
                }
                if (scanClockMemberNameEl) scanClockMemberNameEl.textContent = member.name || 'Member';
                if (scanClockMemberEmailEl) scanClockMemberEmailEl.textContent = member.email || 'No email';
                if (scanClockMemberPhoneEl) scanClockMemberPhoneEl.textContent = member.phone || 'No phone';
                if (scanClockMemberCodeEl) scanClockMemberCodeEl.textContent = member.code || '#---';
                if (scanClockMemberMembershipEl) {
                    scanClockMemberMembershipEl.textContent = member.membership || 'No Membership';
                    scanClockMemberMembershipEl.classList.remove('manual-clock-chip--active', 'manual-clock-chip--inactive');
                    scanClockMemberMembershipEl.classList.add(member.membershipActive ? 'manual-clock-chip--active' : 'manual-clock-chip--inactive');
                }
            };

            const setScanModalState = function (state, options) {
                const config = scanIconMap[state] || scanIconMap.success;
                if (scanClockIconEl) {
                    scanClockIconEl.className = `manual-clock-icon ${config.className}`;
                    scanClockIconEl.innerHTML = `<i class="${config.icon}"></i>`;
                }

                if (scanClockTitleEl) scanClockTitleEl.textContent = options.title || 'Attendance update';
                if (scanClockSubtitleEl) {
                    scanClockSubtitleEl.textContent = options.subtitle || '';
                    scanClockSubtitleEl.classList.toggle('d-none', !options.subtitle);
                }
            };

            const showScanModal = function () {
                if (scanClockModal) {
                    scanClockModal.show();
                } else if (scanClockTitleEl) {
                    alert(scanClockTitleEl.textContent);
                }
            };

            const isErrorMessage = function (message) {
                return /unable|invalid|no data|no valid|already|cannot|error|unexpected|expired/i.test(message);
            };

            function syncCameraButtons() {
                if (enableCameraBtn) {
                    enableCameraBtn.disabled = isCameraRunning || !availableCameras.length;
                }
                if (disableCameraBtn) {
                    disableCameraBtn.disabled = !isCameraRunning;
                }
            }

            function startCamera() {
                if (!scannerInstance || !availableCameras.length) {
                    setCameraStatus('No camera found', 'danger');
                    return;
                }

                scannerInstance.start(availableCameras[activeCameraIndex]).then(function () {
                    isCameraRunning = true;
                    syncCameraButtons();
                    toggleScannerVisibility(true);
                    setCameraStatus('Camera active', 'success');
                }).catch(function (error) {
                    console.error(error);
                    setCameraStatus('Unable to start camera', 'danger');
                    toggleScannerVisibility(false);
                });
            }

            function stopCamera() {
                if (!scannerInstance || !isCameraRunning) {
                    return;
                }

                Promise.resolve(scannerInstance.stop()).then(function () {
                    isCameraRunning = false;
                    syncCameraButtons();
                    setCameraStatus('Camera disabled');
                    toggleScannerVisibility(false);
                }).catch(function (error) {
                    console.error(error);
                    setCameraStatus('Error while stopping camera', 'danger');
                    toggleScannerVisibility(false);
                });
            }

            function extractTokenFromScan(content) {
                if (!content) {
                    return null;
                }

                if (typeof content === 'object') {
                    const objectToken = content.token || content.qr_token || content.qrToken;
                    return typeof objectToken === 'string' ? objectToken.trim() : null;
                }

                const trimmed = String(content).trim();
                if (!trimmed.length) {
                    return null;
                }

                if (trimmed.startsWith('{') && trimmed.endsWith('}')) {
                    try {
                        const parsed = JSON.parse(trimmed);
                        const parsedToken = parsed?.token || parsed?.qr_token || parsed?.qrToken;
                        if (typeof parsedToken === 'string') {
                            return parsedToken.trim();
                        }
                    } catch (error) {
                        console.warn('Unable to parse QR payload', error);
                    }
                }

                return trimmed;
            }

            function sendScannedData(content) {
                const csrfMeta = document.querySelector("meta[name='csrf-token']");
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;
                const token = extractTokenFromScan(content);

                if (!csrfToken) {
                    console.warn('CSRF token missing; skipping attendance lookup');
                    return;
                }

                if (!token) {
                    setCameraStatus('Invalid QR code', 'danger');
                    updateScanMemberCard(normalizeScanUser({}));
                    updateScanWarning(null, null);
                    setScanModalState('error', {
                        title: 'Invalid QR code',
                        subtitle: 'Please scan a valid code.'
                    });
                    showScanModal();
                    return;
                }

                updateScanMemberCard(normalizeScanUser({}));
                updateScanWarning(null, null);
                setScanModalState('loading', {
                    title: 'Updating attendance',
                    subtitle: 'Please wait while we confirm the scan.'
                });
                showScanModal();

                fetch("{{ route('admin.staff-account-management.attendances.scanner2.fetch') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ result: token })
                })
                    .then(response => response.json())
                    .then(data => {
                        const message = data?.data ?? 'Attendance updated.';
                        const status = data?.status || (isErrorMessage(message) ? 'error' : 'success');
                        const member = normalizeScanUser(data?.user || {});
                        updateScanMemberCard(member);
                        updateScanWarning(member, status);

                        if (status === 'clockout') {
                            setScanModalState('clockout', {
                                title: 'Clocked Out Successfully',
                                subtitle: message
                            });
                        } else if (status === 'clockin') {
                            setScanModalState('clockin', {
                                title: 'Clocked In Successfully',
                                subtitle: message
                            });
                        } else if (status === 'error') {
                            setScanModalState('error', {
                                title: 'Unable to update attendance',
                                subtitle: message
                            });
                        } else {
                            setScanModalState('success', {
                                title: 'Attendance updated',
                                subtitle: message
                            });
                        }

                        showScanModal();
                    })
                    .catch(error => {
                        console.error(error);
                        setCameraStatus('Scan failed — see console', 'danger');
                        updateScanMemberCard(normalizeScanUser({}));
                        updateScanWarning(null, null);
                        setScanModalState('error', {
                            title: 'Scan failed',
                            subtitle: 'Unable to process attendance right now.'
                        });
                        showScanModal();
                    });
            }

            toggleScannerVisibility(false);
            syncCameraButtons();

            if (scannerVideo && typeof Instascan !== 'undefined') {
                scannerInstance = new Instascan.Scanner({ video: scannerVideo, mirror: false });

                scannerInstance.addListener('scan', function (content) {
                    sendScannedData(content);
                });

                Instascan.Camera.getCameras().then(function (cameras) {
                    availableCameras = cameras;
                    if (!cameras.length) {
                        toggleScannerVisibility(false);
                        setCameraStatus('No camera found', 'danger');
                    } else {
                        setCameraStatus('Camera ready — enable to start');
                    }
                    syncCameraButtons();
                }).catch(function (error) {
                    console.error(error);
                    toggleScannerVisibility(false);
                    setCameraStatus('Camera access denied', 'danger');
                    syncCameraButtons();
                });
            } else if (scannerVideo) {
                setCameraStatus('Scanner script unavailable', 'danger');
                syncCameraButtons();
                toggleScannerVisibility(false);
            }

            if (enableCameraBtn) {
                enableCameraBtn.addEventListener('click', function () {
                    if (isCameraRunning) {
                        return;
                    }

                    if (typeof Instascan === 'undefined') {
                        setCameraStatus('Scanner script unavailable', 'danger');
                        return;
                    }

                    if (!availableCameras.length) {
                        Instascan.Camera.getCameras().then(function (cameras) {
                            availableCameras = cameras;
                            syncCameraButtons();

                            if (!cameras.length) {
                                toggleScannerVisibility(false);
                                setCameraStatus('No camera found', 'danger');
                                return;
                            }

                            startCamera();
                        }).catch(function (error) {
                            console.error(error);
                            toggleScannerVisibility(false);
                            setCameraStatus('Camera access denied', 'danger');
                        });

                        return;
                    }

                    startCamera();
                });
            }

            if (disableCameraBtn) {
                disableCameraBtn.addEventListener('click', function () {
                    stopCamera();
                });
            }

            window.addEventListener('beforeunload', function () {
                if (scannerInstance && isCameraRunning) {
                    scannerInstance.stop();
                }
                toggleScannerVisibility(false);
            });

            const form = document.getElementById('attendance-filter-form');
            if (!form) {
                return;
            }
            const feedbackModalEl = document.getElementById('actionFeedbackModal');
            if (feedbackModalEl && typeof bootstrap !== 'undefined') {
                const feedbackModal = new bootstrap.Modal(feedbackModalEl);
                feedbackModal.show();
            }

            const statusInput = document.getElementById('attendance-status-filter');
            const chipButtons = form.querySelectorAll('.status-chip');
            const rangeButtons = form.querySelectorAll('.range-chip');
            const startInput = document.getElementById('start-date');
            const endInput = document.getElementById('end-date');
            const printForm = document.getElementById('print-form');
            const printButton = document.getElementById('print-submit-button');
            const printLoader = document.getElementById('print-loader');

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
                    if (statusInput) {
                        statusInput.value = selectedStatus;
                    }

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

            function getStatusBadgeClass(status) {
                if (!status) return 'badge-soft-muted';
                const normalized = status.toLowerCase();
                if (normalized.includes('pending')) return 'badge-soft-warning';
                if (normalized.includes('completed')) return 'badge-soft-success';
                return 'badge-soft-secondary';
            }

            function buildPrintFilters(filters) {
                const chips = [];
                if (filters.show_archived) chips.push({ value: 'Archived view' });
                if (filters.status && filters.status !== 'all') {
                    const statusMap = {
                        open: 'Pending clock-out',
                        completed: 'Completed',
                    };
                    chips.push({ label: 'Status', value: statusMap[filters.status] || filters.status });
                }
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

            function buildPrintRows(items) {
                return items.map((item) => ([
                    item.id ?? '—',
                    `<div class="fw">${item.name || '—'}</div><div class="muted">${item.role || ''}</div>`,
                    item.user_code || '—',
                    item.clock_in || '—',
                    item.clock_out || '—',
                    `<span class="badge ${getStatusBadgeClass(item.status)}">${item.status || '—'}</span>`,
                    `<div>${item.created_at || ''}</div><div class="muted">${item.updated_at || ''}</div>`,
                ]));
            }

            function renderPrintWindow(payload) {
                const items = payload.items || [];
                const filters = buildPrintFilters(payload.filters || {});
                const headers = ['#', 'Member', 'User Code', 'Clock-in', 'Clock-out', 'Status', 'Audit'];
                const rows = buildPrintRows(items);

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
@endsection
