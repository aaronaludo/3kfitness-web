@extends('layouts.admin')
@section('title', 'Classes History')

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
            $hasFilters = ($filters['search'] ?? '') !== '' || ($filters['trainer_id'] ?? null) || ($activeStatus !== 'all') || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null);
        @endphp

        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="title mb-1">Classes History</h2>
                    <p class="text-muted mb-0 small">Finished classes with enrollment counts and admin status.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <a href="{{ route('admin.gym-management.schedules') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-calendar-days me-2"></i>View classes
                    </a>
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('admin.history.classes', array_merge(request()->except(['show_archived', 'page']), [])) }}"
                        >
                            <i class="fa-solid fa-rotate-left me-2"></i>Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary"
                            href="{{ route('admin.history.classes', array_merge(request()->except(['page']), ['show_archived' => 1])) }}"
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
                                <h4 class="fw-semibold mb-1">Completed classes</h4>
                                <p class="text-muted mb-0">
                                    Filter finished sessions by trainer, admin status, or completion window. Showing
                                    {{ $showArchived ? 'archived' : 'active' }} records.
                                </p>
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Classes</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['classes'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Enrollments</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['enrollments'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Trainers</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['trainers'] ?? 0) }}</div>
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

                        <form action="{{ route('admin.history.classes') }}" method="GET" class="row g-3 align-items-end mt-3">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif
                            <div class="col-12 col-lg-3">
                                <label for="search" class="form-label text-muted small fw-semibold">Search class or trainer</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input
                                        type="search"
                                        id="search"
                                        name="search"
                                        class="form-control"
                                        value="{{ $filters['search'] ?? '' }}"
                                        placeholder="e.g. Yoga, YG-01, Jane"
                                    >
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="trainer_id" class="form-label text-muted small fw-semibold">Trainer</label>
                                <select id="trainer_id" name="trainer_id" class="form-select">
                                    <option value="">All trainers</option>
                                    @foreach ($trainerOptions as $trainer)
                                        @php
                                            $trainerName = trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? ''));
                                        @endphp
                                        <option
                                            value="{{ $trainer->id }}"
                                            {{ (string) ($filters['trainer_id'] ?? '') === (string) $trainer->id ? 'selected' : '' }}
                                        >
                                            {{ $trainerName !== '' ? $trainerName : 'Unnamed Trainer' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="status" class="form-label text-muted small fw-semibold">Admin status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="all" {{ $activeStatus === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="approved" {{ $activeStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="pending" {{ $activeStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="rejected" {{ $activeStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="start_date" class="form-label text-muted small fw-semibold">Ended from</label>
                                <input
                                    type="date"
                                    id="start_date"
                                    name="start_date"
                                    class="form-control"
                                    value="{{ $filters['start_date'] ?? '' }}"
                                >
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="end_date" class="form-label text-muted small fw-semibold">Ended to</label>
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
                                    <a href="{{ route('admin.history.classes', $showArchived ? ['show_archived' => 1] : []) }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
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
                                        <th>Class</th>
                                        <th>Trainer</th>
                                        <th>Members</th>
                                        <th>Class Window</th>
                                        <th>Status</th>
                                        <th>Archive</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($classes as $index => $class)
                                        @php
                                            $trainer = $class->user;
                                            $trainerName = $trainer ? trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? '')) : '';
                                            $start = $class->class_start_date ? \Carbon\Carbon::parse($class->class_start_date) : null;
                                            $end = $class->class_end_date ? \Carbon\Carbon::parse($class->class_end_date) : null;
                                            $statusValue = $class->isadminapproved;
                                            $statusMeta = [
                                                0 => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                                                1 => ['label' => 'Approved', 'class' => 'bg-success'],
                                                2 => ['label' => 'Rejected', 'class' => 'bg-danger'],
                                            ][$statusValue] ?? ['label' => 'Pending', 'class' => 'bg-warning text-dark'];
                                            $archiveLabel = (int) ($class->is_archieve ?? 0) === 1 ? 'Archived' : 'Active';
                                        @endphp
                                        <tr>
                                            <td>{{ ($classes->firstItem() ?? 0) + $index }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $class->name }}</div>
                                                <div class="text-muted small">{{ $class->class_code }}</div>
                                            </td>
                                            <td>
                                                @if($trainer)
                                                    <div>{{ $trainerName }}</div>
                                                    <div class="text-muted small">{{ $trainer->email ?? '—' }}</div>
                                                @else
                                                    <span class="text-muted">Not assigned</span>
                                                @endif
                                            </td>
                                            <td>{{ $class->user_schedules_count ?? 0 }}</td>
                                            <td>
                                                @if($start || $end)
                                                    <div>{{ $start ? $start->format('M d, Y g:i A') : '—' }}</div>
                                                    <div class="text-muted small">to {{ $end ? $end->format('M d, Y g:i A') : '—' }}</div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
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
                                                    <a
                                                        href="{{ route('admin.gym-management.schedules.view', $class->id) }}"
                                                        class="btn btn-outline-primary btn-sm"
                                                    >
                                                        View
                                                    </a>
                                                    <a
                                                        href="{{ route('admin.gym-management.schedules.users', $class->id) }}"
                                                        class="btn btn-outline-secondary btn-sm"
                                                    >
                                                        Members
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                No completed classes found{{ $hasFilters ? ' for the selected filters.' : '.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">
                            {{ $classes->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
