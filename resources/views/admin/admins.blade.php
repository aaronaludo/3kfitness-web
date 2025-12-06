@extends('layouts.admin')
@section('title', 'Admins')

@section('content')
    @php
        $users->loadMissing(['role', 'status']);
        $search = trim(request('search', ''));
        $statusFilter = request('status', '');
        $allAdmins = collect($users);
        $statusOptions = $allAdmins
            ->map(function ($admin) {
                return optional($admin->status)->name;
            })
            ->filter()
            ->unique()
            ->values();
        $admins = $allAdmins
            ->filter(function ($admin) use ($search, $statusFilter) {
                $matchesSearch = true;
                if ($search !== '') {
                    $haystack = strtolower(
                        trim(
                            ($admin->first_name ?? '') .
                                ' ' .
                                ($admin->last_name ?? '') .
                                ' ' .
                                ($admin->email ?? '') .
                                ' ' .
                                ($admin->user_code ?? '')
                        )
                    );
                    $matchesSearch = str_contains($haystack, strtolower($search));
                }

                $matchesStatus = true;
                if ($statusFilter !== '') {
                    $matchesStatus = strcasecmp(optional($admin->status)->name ?? '', $statusFilter) === 0;
                }

                return $matchesSearch && $matchesStatus;
            })
            ->values();

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
    @endphp
    <div class="container-fluid">
        <div class="row gy-4">
            <div class="col-12 d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="title mb-1">Admin directory</h2>
                    <p class="text-muted mb-0">Review platform administrators, their roles, and account status.</p>
                </div>
                @if (auth()->guard('admin')->user()->role_id === 4)
                    <div class="d-flex align-items-center gap-2 h-100">
                        <a class="btn btn-danger" href="{{ route('admin.admins.add') }}">
                            <i class="fa-solid fa-plus"></i>&nbsp;&nbsp;&nbsp;Add Admin
                        </a>
                    </div>
                @endif
            </div>

            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <h5 class="fw-semibold mb-1">Filter admins</h5>
                                <p class="text-muted mb-0">Showing {{ $admins->count() }} of {{ $allAdmins->count() }} admins</p>
                            </div>
                            <form action="{{ route('admin.admins.index') }}" method="GET" id="admin-filter-form" class="d-flex flex-wrap align-items-center gap-2">
                                <input type="hidden" name="status" id="admin-status-filter" value="{{ $statusFilter }}">
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input
                                        type="search"
                                        class="form-control rounded-pill ps-5"
                                        name="search"
                                        placeholder="Search admin"
                                        value="{{ $search }}"
                                        aria-label="Search admin"
                                    />
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('admin.admins.index') }}" class="btn btn-light">Reset</a>
                                    <button type="submit" class="btn btn-danger d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </form>
                        </div>
                        @if ($statusOptions->isNotEmpty())
                            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                                <button
                                    type="button"
                                    class="status-chip btn btn-sm rounded-pill px-3 {{ $statusFilter === '' ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                    data-status=""
                                >
                                    All statuses
                                </button>
                                @foreach ($statusOptions as $option)
                                    <button
                                        type="button"
                                        class="status-chip btn btn-sm rounded-pill px-3 {{ $statusFilter === $option ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                        data-status="{{ $option }}"
                                    >
                                        {{ $option }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="box">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
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
                                            <div>{{ optional($admin->created_at)->format('M d, Y') ?? '—' }}</div>
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
                                            <div class="d-flex">
                                                <div class="action-button"><a href="{{ route('admin.admins.view', $admin->id) }}" title="View"><i class="fa-solid fa-eye"></i></a></div>
                                                <div class="action-button"><a href="{{ route('admin.admins.edit', $admin->id) }}" title="Edit"><i class="fa-solid fa-pencil text-primary"></i></a></div>
                                                <div class="action-button">
                                                    <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $admin->id }}" data-id="{{ $admin->id }}" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                        <i class="fa-solid fa-trash text-danger"></i>
                                                    </button>
                                                </div> 
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No admins match the current filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('admin-filter-form');
            const statusInput = document.getElementById('admin-status-filter');
            const chips = document.querySelectorAll('.status-chip');

            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    const status = this.dataset.status ?? '';
                    if (statusInput) {
                        statusInput.value = status;
                    }
                    form?.submit();
                });
            });
        });
    </script>
@endsection
