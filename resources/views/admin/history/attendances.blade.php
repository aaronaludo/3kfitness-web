@extends('layouts.admin')
@section('title', 'Attendances History')

@section('content')
    <div class="container-fluid">
        @php
            $activeStatus = $filters['status'] ?? 'completed';
            $statusLabels = [
                'all' => ['label' => 'All records', 'class' => 'bg-secondary'],
                'open' => ['label' => 'Open', 'class' => 'bg-warning text-dark'],
                'completed' => ['label' => 'Completed', 'class' => 'bg-success'],
            ];
            $showArchived = $filters['show_archived'] ?? false;
            $hasFilters = ($filters['search'] ?? '') !== '' || ($filters['role_id'] ?? null) || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) || $activeStatus !== 'completed';
        @endphp

        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="title mb-1">Attendances History</h2>
                    <p class="text-muted mb-0 small">Completed attendance logs for members, trainers, and staff.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <a href="{{ route('admin.staff-account-management.attendances') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-clipboard-check me-2"></i>Live attendances
                    </a>
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('admin.history.attendances', array_merge(request()->except(['show_archived', 'page']), [])) }}"
                        >
                            <i class="fa-solid fa-rotate-left me-2"></i>Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('admin.history.attendances', array_merge(request()->except(['page']), ['show_archived' => 1])) }}"
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
                                <h4 class="fw-semibold mb-1">Finished attendance logs</h4>
                                <p class="text-muted mb-0">Filter by role, status, or time window to find past check-ins.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Records</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['records'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Completed</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['completed'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">People</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['people'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
                            @foreach ($statusLabels as $key => $label)
                                <span class="badge {{ $activeStatus === $key ? $label['class'] : 'bg-light text-dark' }} px-3 py-2">
                                    {{ $label['label'] }}
                                    @php
                                        $count = $statusTallies[$key] ?? null;
                                    @endphp
                                    @if(!is_null($count))
                                        <span class="ms-2 fw-semibold">{{ $count }}</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>

                        <form action="{{ route('admin.history.attendances') }}" method="GET" class="row g-3 align-items-end mt-3">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif
                            <div class="col-12 col-lg-3">
                                <label for="search" class="form-label text-muted small fw-semibold">Search person</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input
                                        type="search"
                                        id="search"
                                        name="search"
                                        class="form-control"
                                        value="{{ $filters['search'] ?? '' }}"
                                        placeholder="Name, email, phone"
                                    >
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="role_id" class="form-label text-muted small fw-semibold">Role</label>
                                <select id="role_id" name="role_id" class="form-select">
                                    <option value="">All roles</option>
                                    @foreach ($roleOptions as $role)
                                        <option
                                            value="{{ $role->id }}"
                                            {{ (string) ($filters['role_id'] ?? '') === (string) $role->id ? 'selected' : '' }}
                                        >
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="status" class="form-label text-muted small fw-semibold">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="all" {{ $activeStatus === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="completed" {{ $activeStatus === 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="open" {{ $activeStatus === 'open' ? 'selected' : '' }}>Open</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="start_date" class="form-label text-muted small fw-semibold">Clock-in from</label>
                                <input
                                    type="date"
                                    id="start_date"
                                    name="start_date"
                                    class="form-control"
                                    value="{{ $filters['start_date'] ?? '' }}"
                                >
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="end_date" class="form-label text-muted small fw-semibold">Clock-in to</label>
                                <input
                                    type="date"
                                    id="end_date"
                                    name="end_date"
                                    class="form-control"
                                    value="{{ $filters['end_date'] ?? '' }}"
                                >
                            </div>
                            <div class="col-12 d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                @if ($hasFilters)
                                    <a href="{{ route('admin.history.attendances', $showArchived ? ['show_archived' => 1] : []) }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                                @endif
                                <button type="submit" class="btn btn-danger">
                                    Apply
                                </button>
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
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Contact</th>
                                        <th>Clock-in</th>
                                        <th>Clock-out</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Archive</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($attendances as $index => $attendance)
                                        @php
                                            $person = $attendance->user;
                                            $roleName = $person && $person->role ? $person->role->name : 'Unknown';
                                            $clockIn = $attendance->clockin_at ? \Carbon\Carbon::parse($attendance->clockin_at) : null;
                                            $clockOut = $attendance->clockout_at ? \Carbon\Carbon::parse($attendance->clockout_at) : null;
                                            $durationMinutes = ($clockIn && $clockOut) ? $clockIn->diffInMinutes($clockOut) : null;
                                            $durationText = $durationMinutes !== null
                                                ? sprintf('%dh %02dm', intdiv($durationMinutes, 60), $durationMinutes % 60)
                                                : '—';
                                            $statusValue = $attendance->clockout_at ? 'completed' : 'open';
                                            $statusMeta = [
                                                'open' => ['label' => 'Open', 'class' => 'bg-warning text-dark'],
                                                'completed' => ['label' => 'Completed', 'class' => 'bg-success'],
                                            ][$statusValue];
                                            $archiveLabel = (int) ($attendance->is_archive ?? 0) === 1 ? 'Archived' : 'Active';
                                        @endphp
                                        <tr>
                                            <td>{{ ($attendances->firstItem() ?? 0) + $index }}</td>
                                            <td>
                                                <div class="fw-semibold">
                                                    {{ $person ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) : 'Unknown user' }}
                                                </div>
                                                <div class="text-muted small">ID: {{ $person->id ?? '—' }}</div>
                                            </td>
                                            <td>{{ $roleName }}</td>
                                            <td>
                                                <div>{{ $person->email ?? '—' }}</div>
                                                <div class="text-muted small">{{ $person->phone_number ?? '—' }}</div>
                                            </td>
                                            <td>
                                                @if($clockIn)
                                                    {{ $clockIn->format('M d, Y g:i A') }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($clockOut)
                                                    {{ $clockOut->format('M d, Y g:i A') }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $durationText }}</td>
                                            <td>
                                                <span class="badge {{ $statusMeta['class'] }} px-3 py-2">
                                                    {{ $statusMeta['label'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $archiveLabel === 'Archived' ? 'bg-secondary' : 'bg-success' }} px-3 py-2">
                                                    {{ $archiveLabel }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                                    @if($person)
                                                        <a
                                                            href="{{ route('admin.users.view', $person->id) }}"
                                                            class="btn btn-outline-secondary btn-sm"
                                                        >
                                                            User
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4 text-muted">
                                                No attendance records found{{ $hasFilters ? ' for the selected filters.' : '.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">
                            {{ $attendances->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
