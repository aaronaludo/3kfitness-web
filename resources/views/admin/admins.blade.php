@extends('layouts.admin')
@section('title', 'Admins')

@section('content')
    @php
        $users->loadMissing(['role', 'status']);
        $search = trim(request('search', ''));
        $statusFilter = request('status', '');
        $startDateValue = request('start_date');
        $endDateValue = request('end_date');
        $startDate = null;
        $endDate = null;
        if (!empty($startDateValue)) {
            try {
                $startDate = \Carbon\Carbon::parse($startDateValue)->startOfDay();
            } catch (\Exception $e) {
                $startDate = null;
            }
        }
        if (!empty($endDateValue)) {
            try {
                $endDate = \Carbon\Carbon::parse($endDateValue)->endOfDay();
            } catch (\Exception $e) {
                $endDate = null;
            }
        }
        $showArchived = request()->boolean('show_archived');
        $allAdmins = collect($users);
        $visibleAdmins = $allAdmins
            ->filter(function ($admin) use ($showArchived) {
                $isArchived = (int) ($admin->is_archive ?? 0) === 1;
                return $showArchived ? $isArchived : !$isArchived;
            })
            ->values();
        $statusOptions = $visibleAdmins
            ->map(function ($admin) {
                return optional($admin->status)->name;
            })
            ->filter()
            ->unique()
            ->values();
        $statusTallies = $visibleAdmins
            ->groupBy(function ($admin) {
                return optional($admin->status)->name;
            })
            ->filter(function ($group, $name) {
                return !empty($name);
            })
            ->map
            ->count();
        $statusChipOptions = collect([
            [
                'key' => '',
                'label' => 'All statuses',
                'count' => $visibleAdmins->count(),
            ],
        ])->merge($statusOptions->map(function ($status) use ($statusTallies) {
            return [
                'key' => $status,
                'label' => $status,
                'count' => $statusTallies->get($status),
            ];
        }));
        $advancedFiltersOpen = request()->filled('start_date') || request()->filled('end_date');
        $filteredAdmins = $visibleAdmins
            ->filter(function ($admin) use ($search, $statusFilter, $startDate, $endDate) {
                $matchesSearch = true;
                $fullName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));
                $roleName = optional($admin->role)->name ?? 'Admin';
                $statusName = optional($admin->status)->name ?? '';

                if ($search !== '') {
                    $needle = strtolower($search);
                    $haystack = strtolower(
                        trim(
                            ($admin->id ?? '') .
                                ' ' .
                                ($admin->user_code ?? '') .
                                ' ' .
                                $fullName .
                                ' ' .
                                ($admin->email ?? '') .
                                ' ' .
                                ($admin->phone_number ?? '') .
                                ' ' .
                                ($admin->address ?? '') .
                                ' ' .
                                $roleName .
                                ' ' .
                                $statusName .
                                ' ' .
                                ($admin->created_by ?? '')
                        )
                    );
                    $matchesSearch = str_contains($haystack, $needle);
                }

                $matchesStatus = true;
                if ($statusFilter !== '') {
                    $matchesStatus = strcasecmp(optional($admin->status)->name ?? '', $statusFilter) === 0;
                }

                $matchesDate = true;
                if ($startDate || $endDate) {
                    $createdAt = $admin->created_at ?? null;
                    if ($createdAt && !($createdAt instanceof \Carbon\Carbon)) {
                        try {
                            $createdAt = \Carbon\Carbon::parse($createdAt);
                        } catch (\Exception $e) {
                            $createdAt = null;
                        }
                    }
                    if (!$createdAt) {
                        $matchesDate = false;
                    } else {
                        if ($startDate && $createdAt->lt($startDate)) {
                            $matchesDate = false;
                        }
                        if ($endDate && $createdAt->gt($endDate)) {
                            $matchesDate = false;
                        }
                    }
                }

                return $matchesSearch && $matchesStatus && $matchesDate;
            })
            ->values();
        $adminsCount = $filteredAdmins->count();
        $perPage = 10;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $admins = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredAdmins->forPage($currentPage, $perPage)->values(),
            $adminsCount,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        $statusBadge = function ($statusName) {
            $normalized = strtolower($statusName ?? '');
            return [
                'class' => match (true) {
                    $normalized === 'active', $normalized === 'enabled' => 'success',
                    $normalized === 'pending' => 'warning',
                    $normalized === 'inactive', $normalized === 'disabled' => 'secondary',
                    default => 'light',
                },
                'hint' => match (true) {
                    $normalized === 'active', $normalized === 'enabled' => 'Can sign in',
                    $normalized === 'pending' => 'Access pending',
                    $normalized === 'inactive', $normalized === 'disabled' => 'Access restricted',
                    default => 'Status unknown',
                },
            ];
        };
        $canEditAdmin = Route::has('admin.admins.edit');
        $mapAdminForPrint = function ($admin) use ($statusBadge) {
            $statusName = optional($admin->status)->name ?? '—';
            $statusMeta = $statusBadge($statusName);

            return [
                'id' => $admin->id ?? '—',
                'user_code' => $admin->user_code ?? '',
                'name' => trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '')),
                'email' => $admin->email ?? '',
                'phone' => $admin->phone_number ?? '',
                'address' => $admin->address ?? '',
                'role' => optional($admin->role)->name ?? 'Admin',
                'status' => $statusName,
                'status_hint' => $statusMeta['hint'] ?? '',
                'created_at' => optional($admin->created_at)->format('F j, Y g:iA') ?? '',
                'created_by' => $admin->created_by ?? '',
            ];
        };
        $printAdmins = $filteredAdmins->map($mapAdminForPrint);
        $printAllAdmins = $visibleAdmins->map($mapAdminForPrint);
        $printUser = auth()->user();
        $printUserName = $printUser
            ? trim(($printUser->first_name ?? '') . ' ' . ($printUser->last_name ?? ''))
            : '';
        if ($printUser && $printUserName === '') {
            $printUserName = $printUser->name ?? $printUser->email ?? '';
        }
        $printUserRole = $printUser ? optional($printUser->role)->name : null;
        $printGeneratedBy = $printUserName !== '' ? $printUserName : '—';
        if ($printUserRole) {
            $printGeneratedBy .= " ({$printUserRole})";
        }
        $printPayload = [
            'title' => $showArchived ? 'Archived admins' : 'Admin directory',
            'generated_at' => now()->format('F j, Y g:iA'),
            'meta' => [
                'generated_by' => $printGeneratedBy,
            ],
            'filters' => [
                'search' => $search ?: null,
                'status' => $statusFilter ?: 'all',
                'start' => $startDateValue ?: null,
                'end' => $endDateValue ?: null,
                'show_archived' => $showArchived,
            ],
            'count' => $printAdmins->count(),
            'items' => $printAdmins,
        ];
        $printAllPayload = [
            'title' => $showArchived ? 'Archived admins (all)' : 'Admin directory (all)',
            'generated_at' => now()->format('F j, Y g:iA'),
            'meta' => [
                'generated_by' => $printGeneratedBy,
            ],
            'filters' => [
                'search' => $search ?: null,
                'status' => $statusFilter ?: 'all',
                'start' => $startDateValue ?: null,
                'end' => $endDateValue ?: null,
                'show_archived' => $showArchived,
                'scope' => 'all',
            ],
            'count' => $printAllAdmins->count(),
            'items' => $printAllAdmins,
        ];
    @endphp
    <div class="container-fluid">
        <div class="row gy-4">
            <div class="col-12 d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="title mb-1">Admin directory</h2>
                    <p class="text-muted mb-0">Review platform administrators, their roles, and account status.</p>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2 h-100">
                    @if (auth()->guard('admin')->user()->role_id === 4)
                        <a class="btn btn-danger" href="{{ route('admin.admins.add') }}">
                            <i class="fa-solid fa-plus"></i>&nbsp;&nbsp;&nbsp;Add Admin
                        </a>
                    @endif
                    <form action="{{ route('admin.print.preview') }}" method="POST" id="print-form" target="_blank">
                        @csrf
                        <input type="hidden" name="payload" id="print-payload-input">
                        <button
                            class="btn btn-danger"
                            type="submit"
                            id="print-submit-button"
                            data-print='@json($printPayload)'
                            data-print-all='@json($printAllPayload)'
                            aria-label="Open printable/PDF view of filtered admins"
                        >
                            <i class="fa-solid fa-print"></i>
                            <span id="print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('admin.admins.index', request()->except(['show_archived', 'page'])) }}"
                        >
                            <i class="fa-solid fa-rotate-left"></i>&nbsp;&nbsp;Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('admin.admins.index', array_merge(request()->except(['page']), ['show_archived' => 1])) }}"
                        >
                            <i class="fa-solid fa-box-archive"></i>&nbsp;&nbsp;View archived
                        </a>
                    @endif
                </div>
            </div>

            <div class="col-12 mb-20">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Overview</span>
                                <h4 class="fw-semibold mb-1">Admin directory</h4>
                                <p class="text-muted mb-0">Review platform administrators, their roles, and account status.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">
                                    @if ($showArchived)
                                        Showing {{ $admins->total() }} archived admins
                                    @else
                                        Showing {{ $admins->total() }} results
                                    @endif
                                </span>
                            </div>
                        </div>

                        <form action="{{ route('admin.admins.index') }}" method="GET" id="admin-filter-form" class="mt-4">
                            <input type="hidden" name="status" id="admin-status-filter" value="{{ $statusFilter }}">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @foreach ($statusChipOptions as $option)
                                        <button
                                            type="button"
                                            class="status-chip btn btn-sm rounded-pill px-3 {{ $statusFilter === $option['key'] ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                            data-status="{{ $option['key'] }}"
                                        >
                                            {{ $option['label'] }}
                                            @if (!is_null($option['count']))
                                                <span class="badge bg-transparent {{ $statusFilter === $option['key'] ? 'text-white' : 'text-dark' }} fw-semibold ms-2">{{ $option['count'] }}</span>
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
                                                name="search"
                                                placeholder="Search admin by name, code, email, role"
                                                value="{{ $search }}"
                                                aria-label="Search admin"
                                            />
                                        </div>
                                    </div>

                                    <a
                                        href="{{ $showArchived ? route('admin.admins.index', ['show_archived' => 1]) : route('admin.admins.index') }}"
                                        class="btn btn-link text-decoration-none text-muted px-0"
                                    >
                                        Reset
                                    </a>

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#adminFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="adminFiltersModal" tabindex="-1" aria-labelledby="adminFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="adminFiltersModalLabel">Advanced filters</h5>
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
                                                            <label for="admin-start-date" class="form-label small text-muted mb-1">Start date</label>
                                                            <input
                                                                type="date"
                                                                id="admin-start-date"
                                                                class="form-control rounded-3"
                                                                name="start_date"
                                                                value="{{ request('start_date') }}"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label for="admin-end-date" class="form-label small text-muted mb-1">End date</label>
                                                            <input
                                                                type="date"
                                                                id="admin-end-date"
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
            
            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success my-3">
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
            </div>
            
            <div class="col-12">
                <div class="box">
                    <div class="table-responsive mb-3">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>User Code</th>
                                    <th>Admin</th>
                                    <th>Role</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($admins as $admin)
                                    @php
                                        $fullName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));
                                        $statusName = optional($admin->status)->name ?? '—';
                                        $statusMeta = $statusBadge($statusName);
                                    @endphp
                                    <tr>
                                        <td>{{ $admin->id }}</td>
                                        <td>
                                            <span class="text-muted small">{{ optional($admin)->user_code ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $fullName ?: 'Admin' }}</div>
                                            <div class="text-muted small">{{ $admin->email ?? 'No email' }}</div>
                                            @if ($admin->user_code)
                                                <div class="text-muted small">Code: {{ $admin->user_code }}</div>
                                            @endif
                                        </td>
                                        <td>{{ optional($admin->role)->name ?? 'Admin' }}</td>
                                        <td>
                                            <div>{{ $admin->phone_number ?? '—' }}</div>
                                            <div class="text-muted small">{{ $admin->address ?? '' }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success px-3 py-2">{{ $statusName }}</span>
                                        </td>
                                        <td>
                                            <div>{{ optional($admin->created_at)->format('F j, Y') ?? '—' }}</div>
                                            <div class="text-muted small">{{ $admin->created_by ?? '' }}</div>
                                        </td>
                                        <td>
                                            {{-- <div class="d-flex gap-2">
                                                <a
                                                    href="{{ route('admin.admins.view', $admin->id) }}"
                                                    class="btn btn-light btn-sm border"
                                                    title="View"
                                                >
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                @if ($canEditAdmin)
                                                    <a
                                                        href="{{ route('admin.admins.edit', $admin->id) }}"
                                                        class="btn btn-light btn-sm border"
                                                        title="Edit"
                                                    >
                                                        <i class="fa-solid fa-pencil text-primary"></i>
                                                    </a>
                                                @else
                                                    <button class="btn btn-light btn-sm border disabled" type="button" title="Edit route not configured">
                                                        <i class="fa-solid fa-pencil text-primary"></i>
                                                    </button>
                                                @endif
                                            </div> --}}
                                            <div class="d-flex gap-2">
                                                <div class="action-button">
                                                    <a href="{{ route('admin.admins.view', $admin->id) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                                </div>
                                                @if (!$showArchived)
                                                    <div class="action-button">
                                                        <a href="{{ route('admin.admins.edit', $admin->id) }}" title="Edit"><i class="fa-solid fa-pencil text-primary"></i></a>
                                                    </div>
                                                    <div class="action-button">
                                                        <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $admin->id }}" data-id="{{ $admin->id }}" title="Archive" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                            <i class="fa-solid fa-box-archive text-danger"></i>
                                                        </button>
                                                    </div>
                                                @else
                                                    <div class="action-button">
                                                        <button type="button" data-bs-toggle="modal" data-bs-target="#restoreModal-{{ $admin->id }}" data-id="{{ $admin->id }}" title="Restore" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                            <i class="fa-solid fa-rotate-left text-success"></i>
                                                        </button>
                                                    </div>
                                                    <div class="action-button">
                                                        <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $admin->id }}" data-id="{{ $admin->id }}" title="Delete permanently" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                            <i class="fa-solid fa-trash text-danger"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <div class="modal fade" id="deleteModal-{{ $admin->id }}" tabindex="-1" aria-labelledby="deleteModalLabel-{{ $admin->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow rounded-4">
                                                <div class="modal-header border-0 pb-0">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="badge bg-danger bg-opacity-10 text-danger rounded-circle p-3">
                                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                                        </div>
                                                        <div>
                                                            <p class="text-uppercase text-muted small mb-1">{{ $showArchived ? 'Delete admin' : 'Archive admin' }}</p>
                                                            <h5 class="fw-semibold mb-0" id="deleteModalLabel-{{ $admin->id }}">
                                                                {{ $admin->email }} ({{ $admin->first_name }} {{ $admin->last_name }})
                                                            </h5>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('admin.admins.delete') }}" method="POST" id="admin-delete-form-{{ $admin->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="id" value="{{ $admin->id }}">
                                                    <div class="modal-body pt-3">
                                                        <div class="alert alert-danger bg-opacity-10 text-danger border-0 rounded-3">
                                                            @if ($showArchived)
                                                                Deleting will permanently remove this admin account. This action cannot be undone.
                                                            @else
                                                                Archiving will move this admin to the archived list. You can restore the account later if needed.
                                                            @endif
                                                        </div>
                                                        <label class="form-label fw-semibold mt-2">Confirm with your password</label>
                                                        <div class="input-group">
                                                            <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                            <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button class="btn btn-danger" type="submit" id="admin-delete-submit-{{ $admin->id }}">
                                                            <span id="admin-delete-loader-{{ $admin->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                            {{ $showArchived ? 'Delete admin' : 'Archive admin' }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($showArchived)
                                        <div class="modal fade" id="restoreModal-{{ $admin->id }}" tabindex="-1" aria-labelledby="restoreModalLabel-{{ $admin->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow rounded-4">
                                                    <div class="modal-header border-0 pb-0">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="badge bg-success bg-opacity-10 text-success rounded-circle p-3">
                                                                <i class="fa-solid fa-rotate-left"></i>
                                                            </div>
                                                            <div>
                                                                <p class="text-uppercase text-muted small mb-1">Restore admin</p>
                                                                <h5 class="fw-semibold mb-0" id="restoreModalLabel-{{ $admin->id }}">
                                                                    {{ $admin->email }} ({{ $admin->first_name }} {{ $admin->last_name }})
                                                                </h5>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('admin.admins.restore') }}" method="POST" id="admin-restore-form-{{ $admin->id }}">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $admin->id }}">
                                                        <div class="modal-body pt-3">
                                                            <div class="alert alert-success bg-opacity-10 text-success border-0 rounded-3">
                                                                Restoring will return this admin to the active list.
                                                            </div>
                                                            <label class="form-label fw-semibold mt-2">Confirm with your password</label>
                                                            <div class="input-group">
                                                                <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                            <button class="btn btn-success" type="submit" id="admin-restore-submit-{{ $admin->id }}">
                                                                <span id="admin-restore-loader-{{ $admin->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                Restore admin
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No admins match the current filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="text-muted small">
                            @if ($showArchived)
                                Showing {{ $admins->firstItem() ?? 0 }} to {{ $admins->lastItem() ?? 0 }} of {{ $admins->total() }} archived admins
                            @else
                                Showing {{ $admins->firstItem() ?? 0 }} to {{ $admins->lastItem() ?? 0 }} of {{ $admins->total() }} results
                            @endif
                        </div>
                        <div class="ms-auto">
                            {{ $admins->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteForms = document.querySelectorAll('[id^="admin-delete-form-"]');
            deleteForms.forEach(function (form) {
                form.addEventListener('submit', function () {
                    const submitId = form.getAttribute('id')?.replace('form', 'submit') || '';
                    const loaderId = form.getAttribute('id')?.replace('form', 'loader') || '';
                    const submitButton = submitId ? document.getElementById(submitId) : null;
                    const loader = loaderId ? document.getElementById(loaderId) : null;

                    if (submitButton) submitButton.disabled = true;
                    if (loader) loader.classList.remove('d-none');
                });
            });

            const restoreForms = document.querySelectorAll('[id^="admin-restore-form-"]');
            restoreForms.forEach(function (form) {
                form.addEventListener('submit', function () {
                    const submitId = form.getAttribute('id')?.replace('form', 'submit') || '';
                    const loaderId = form.getAttribute('id')?.replace('form', 'loader') || '';
                    const submitButton = submitId ? document.getElementById(submitId) : null;
                    const loader = loaderId ? document.getElementById(loaderId) : null;

                    if (submitButton) submitButton.disabled = true;
                    if (loader) loader.classList.remove('d-none');
                });
            });

            document.querySelectorAll('.reveal-button').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const input = this.parentElement?.querySelector('.password-input');
                    if (!input) return;
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    this.textContent = isPassword ? 'Hide' : 'Show';
                });
            });

            const feedbackModalEl = document.getElementById('actionFeedbackModal');
            if (feedbackModalEl && typeof bootstrap !== 'undefined') {
                const feedbackModal = bootstrap.Modal.getOrCreateInstance(feedbackModalEl);
                feedbackModal.show();
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printButton = document.getElementById('print-submit-button');
            const printForm = document.getElementById('print-form');
            const printLoader = document.getElementById('print-loader');
            const payloadInput = document.getElementById('print-payload-input');

            function buildFilters(filters) {
                const chips = [];
                if (filters.show_archived) chips.push({ value: 'Archived view' });
                if (filters.status && filters.status !== 'all') chips.push({ label: 'Status', value: filters.status });
                if (filters.search) {
                    chips.push({ label: 'Search', value: filters.search });
                }
                if (filters.start || filters.end) {
                    let rangeLabel = '';
                    if (filters.start && filters.end) {
                        rangeLabel = `${filters.start} to ${filters.end}`;
                    } else if (filters.start) {
                        rangeLabel = `From ${filters.start}`;
                    } else {
                        rangeLabel = `Until ${filters.end}`;
                    }
                    chips.push({ label: 'Created', value: rangeLabel });
                }
                return chips;
            }

            function buildRows(items) {
                return (items || []).map(function (item) {
                    const status = item.status || '—';
                    const statusHint = item.status_hint ? `<div class="muted">${item.status_hint}</div>` : '';
                    const addressLine = item.address ? `<div class="muted">${item.address}</div>` : '';
                    return [
                        item.id ?? '—',
                        item.user_code || '—',
                        `<div class="fw">${item.name || '—'}</div><div class="muted">${item.email || ''}</div>`,
                        item.role || 'Admin',
                        `<div>${item.phone || '—'}</div>${addressLine}`,
                        `<div class="fw">${status}</div>${statusHint}`,
                        `<div>${item.created_by || '—'}</div><div class="muted">${item.created_at || ''}</div>`,
                    ];
                });
            }

            function buildPreviewPayload(basePayload, headers, rows, filters) {
                if (window.PrintPreview && typeof PrintPreview.buildPayload === 'function') {
                    return PrintPreview.buildPayload(basePayload, headers, rows, filters);
                }

                return {
                    title: basePayload?.title || 'Print preview',
                    generated_at: basePayload?.generated_at || '',
                    count: typeof basePayload?.count !== 'undefined' && basePayload?.count !== null ? basePayload.count : rows.length,
                    filters,
                    table: { headers, rows },
                    meta: basePayload?.meta || {},
                    notes: basePayload?.notes || null,
                };
            }

            function renderPrintWindow(payload, headers, rows, filters) {
                return window.PrintPreview
                    ? PrintPreview.tryOpen(payload, headers, rows, filters)
                    : false;
            }

            if (printButton && printForm) {
                printButton.addEventListener('click', async function (e) {
                    const rawPayload = printButton.dataset.print;
                    const rawAllPayload = printButton.dataset.printAll;
                    if (!rawPayload) {
                        return;
                    }

                    e.preventDefault();
                    if (printLoader) printLoader.classList.remove('d-none');
                    printButton.disabled = true;

                    let payload = null;
                    let allPayload = null;
                    try {
                        payload = JSON.parse(rawPayload);
                    } catch (error) {
                        payload = null;
                    }
                    try {
                        allPayload = rawAllPayload ? JSON.parse(rawAllPayload) : null;
                    } catch (error) {
                        allPayload = null;
                    }

                    const scope = window.PrintPreview && typeof PrintPreview.chooseScope === 'function'
                        ? await PrintPreview.chooseScope()
                        : 'current';

                    if (!scope) {
                        printButton.disabled = false;
                        if (printLoader) printLoader.classList.add('d-none');
                        return;
                    }

                    const payloadToUse = scope === 'all' && allPayload ? allPayload : payload;
                    const items = payloadToUse && payloadToUse.items
                        ? (Array.isArray(payloadToUse.items) ? payloadToUse.items : Object.values(payloadToUse.items))
                        : [];
                    const headers = ['#', 'User code', 'Admin', 'Role', 'Contact', 'Status', 'Created By'];
                    const filters = buildFilters(payloadToUse?.filters || {});
                    const rows = buildRows(items);

                    const handled = payloadToUse ? renderPrintWindow(payloadToUse, headers, rows, filters) : false;

                    if (!handled && payloadInput) {
                        payloadInput.value = JSON.stringify(buildPreviewPayload(payloadToUse || {}, headers, rows, filters));
                        printForm.submit();
                    }

                    printButton.disabled = false;
                    if (printLoader) printLoader.classList.add('d-none');
                });
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('admin-filter-form');
            if (!form) {
                return;
            }
            const statusInput = document.getElementById('admin-status-filter');
            const chipButtons = form.querySelectorAll('.status-chip');
            const rangeButtons = form.querySelectorAll('.range-chip');
            const startInput = document.getElementById('admin-start-date');
            const endInput = document.getElementById('admin-end-date');

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
                    const status = this.dataset.status ?? '';
                    if (statusInput) {
                        statusInput.value = status;
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
        });
    </script>
@endsection
