@extends('layouts.admin')
@section('title', 'Class Enrollees')

@section('content')
    <div class="container-fluid">
        <div class="row align-items-center mb-4">
            <div class="col-lg-8">
                <h2 class="title mb-1">{{ $schedule->name }}</h2>
                <div class="text-muted small">
                    Class code: <span class="fw-semibold">{{ $schedule->class_code }}</span>
                    <span class="mx-2">•</span>
                    Trainer:
                    <span class="fw-semibold">
                        {{ $schedule->trainer_id == 0 ? 'No trainer assigned' : trim((optional($schedule->user)->first_name ?? '') . ' ' . (optional($schedule->user)->last_name ?? '')) }}
                    </span>
                </div>
            </div>
            <div class="col-lg-4 d-flex justify-content-lg-end gap-2 mt-3 mt-lg-0">
                <a href="{{ route('admin.gym-management.schedules.edit', $schedule->id) }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-pencil me-2"></i>Edit class
                </a>
                <a href="{{ route('admin.gym-management.schedules') }}" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Back to schedules
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-semibold mb-1">Schedule window</div>
                        <div class="fw-semibold">{{ $schedule->class_start_date }} — {{ $schedule->class_end_date }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-semibold mb-1">Capacity</div>
                        <div class="fw-semibold">{{ $schedule->user_schedules_count }} of {{ $schedule->slots }} slots filled</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted text-uppercase small fw-semibold mb-1">Status</div>
                        @php
                            $now = now();
                            $startDate = \Carbon\Carbon::parse($schedule->class_start_date);
                            $endDate = \Carbon\Carbon::parse($schedule->class_end_date);
                            $statusLabel = 'Past';
                            $badgeClass = 'bg-primary';

                            if ($now->lt($startDate)) {
                                $statusLabel = 'Upcoming';
                                $badgeClass = 'bg-warning text-dark';
                            } elseif ($now->between($startDate, $endDate)) {
                                $statusLabel = 'Ongoing';
                                $badgeClass = 'bg-success';
                            }
                        @endphp
                        <span class="badge {{ $badgeClass }} px-3 py-2">{{ $statusLabel }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('admin.gym-management.schedules.users', $schedule->id) }}" method="GET" class="row g-2 align-items-center mb-3">
                    <div class="col-md-6 col-lg-4">
                        <label for="search" class="visually-hidden">Search enrolled users</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input
                                type="text"
                                id="search"
                                name="search"
                                class="form-control"
                                value="{{ $search }}"
                                placeholder="Search by name, email, or phone"
                            >
                        </div>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-danger">
                            Apply
                        </button>
                    </div>
                    @if($search !== '')
                        <div class="col-md-auto">
                            <a href="{{ route('admin.gym-management.schedules.users', $schedule->id) }}" class="btn btn-link text-decoration-none text-muted">
                                Reset
                            </a>
                        </div>
                    @endif
                </form>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($userSchedules as $index => $userSchedule)
                                @php
                                    $user = $userSchedule->user;
                                    $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';
                                @endphp
                                <tr>
                                    <td>{{ ($userSchedules->firstItem() ?? 0) + $index }}</td>
                                    <td>{{ $fullName !== '' ? $fullName : 'Unknown user' }}</td>
                                    <td>{{ $user->email ?? '—' }}</td>
                                    <td>{{ $user->phone_number ?? '—' }}</td>
                                    <td>{{ optional($userSchedule->created_at)->format('M d, Y h:i A') }}</td>
                                    <td class="text-center">
                                        @if($user)
                                            <a
                                                href="{{ route('admin.gym-management.members.view', $user->id) }}"
                                                class="btn btn-outline-secondary btn-sm"
                                            >
                                                View profile
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No enrolled users found{{ $search !== '' ? ' for your search.' : '.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div class="text-muted small">
                        Showing {{ $userSchedules->count() }} of {{ $userSchedules->total() }} enrolled users
                    </div>
                    {{ $userSchedules->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
