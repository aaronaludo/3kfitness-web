@extends('layouts.admin')
@section('title', 'Enrollment History')

@section('content')
    <div class="container-fluid">
        @php
            $hasFilters = ($filters['search'] ?? '') !== '' || ($filters['class_id'] ?? null) || ($filters['start_date'] ?? null) || ($filters['end_date'] ?? null);
        @endphp

        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="title mb-1">Enrollment History</h2>
                    <p class="text-muted mb-0 small">Past member enrollments for classes that have already finished.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.gym-management.schedules') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-calendar-days me-2"></i>View classes
                    </a>
                </div>
            </div>

            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">History</span>
                                <h4 class="fw-semibold mb-1">Completed class enrollments</h4>
                                <p class="text-muted mb-0">Search members who joined completed classes, filter by class, and review when they enrolled.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Enrollments</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['total'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Unique members</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['members'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Classes</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['classes'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('admin.history.class-enrollments') }}" method="GET" class="row g-3 align-items-end mt-3">
                            <div class="col-12 col-lg-4">
                                <label for="search" class="form-label text-muted small fw-semibold">Search member, class, or code</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input
                                        type="search"
                                        id="search"
                                        name="search"
                                        class="form-control"
                                        value="{{ $filters['search'] ?? '' }}"
                                        placeholder="e.g. Jane Doe, Yoga, YG-01"
                                    >
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="class_id" class="form-label text-muted small fw-semibold">Class</label>
                                <select id="class_id" name="class_id" class="form-select">
                                    <option value="">All completed classes</option>
                                    @foreach ($classOptions as $class)
                                        <option
                                            value="{{ $class->id }}"
                                            {{ (string) ($filters['class_id'] ?? '') === (string) $class->id ? 'selected' : '' }}
                                        >
                                            {{ $class->name }} ({{ $class->class_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="start_date" class="form-label text-muted small fw-semibold">Joined from</label>
                                <input
                                    type="date"
                                    id="start_date"
                                    name="start_date"
                                    class="form-control"
                                    value="{{ $filters['start_date'] ?? '' }}"
                                >
                            </div>
                            <div class="col-12 col-sm-6 col-lg-2">
                                <label for="end_date" class="form-label text-muted small fw-semibold">Joined to</label>
                                <input
                                    type="date"
                                    id="end_date"
                                    name="end_date"
                                    class="form-control"
                                    value="{{ $filters['end_date'] ?? '' }}"
                                >
                            </div>
                            <div class="col-12 col-sm-6 col-lg-1 d-flex align-items-end gap-2 flex-wrap">
                                <button type="submit" class="btn btn-danger flex-fill">Apply</button>
                                @if ($hasFilters)
                                    <a href="{{ route('admin.history.class-enrollments') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>
                                @endif
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
                                        <th>Class</th>
                                        <th>Trainer</th>
                                        <th>Joined</th>
                                        <th>Class Window</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($enrollments as $index => $enrollment)
                                        @php
                                            $member = $enrollment->user;
                                            $class = $enrollment->schedule;
                                            $trainer = optional($class)->user;
                                            $start = $class && $class->class_start_date ? \Carbon\Carbon::parse($class->class_start_date) : null;
                                            $end = $class && $class->class_end_date ? \Carbon\Carbon::parse($class->class_end_date) : null;
                                            $joinedAt = $enrollment->created_at ? $enrollment->created_at->format('M d, Y g:i A') : '—';
                                            $fullName = $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : '';
                                        @endphp
                                        <tr>
                                            <td>{{ ($enrollments->firstItem() ?? 0) + $index }}</td>
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
                                                @if($class)
                                                    <div class="fw-semibold">{{ $class->name }}</div>
                                                    <div class="text-muted small">{{ $class->class_code }}</div>
                                                @else
                                                    <span class="text-muted">Class unavailable</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($trainer)
                                                    {{ trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? '')) }}
                                                @else
                                                    <span class="text-muted">Not assigned</span>
                                                @endif
                                            </td>
                                            <td>{{ $joinedAt }}</td>
                                            <td>
                                                @if($start || $end)
                                                    <div>{{ $start ? $start->format('M d, Y g:i A') : '—' }}</div>
                                                    <div class="text-muted small">to {{ $end ? $end->format('M d, Y g:i A') : '—' }}</div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
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
                                                    @if($class)
                                                        <a
                                                            href="{{ route('admin.gym-management.schedules.view', $class->id) }}"
                                                            class="btn btn-outline-primary btn-sm"
                                                        >
                                                            Class
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                No past enrollments found{{ $hasFilters ? ' for the selected filters.' : '.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="text-muted small">
                            Showing {{ $enrollments->count() }} of {{ $enrollments->total() }} enrollments
                        </div>
                        {{ $enrollments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
