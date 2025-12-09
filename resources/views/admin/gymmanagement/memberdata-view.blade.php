@extends('layouts.admin')
@section('title', 'View Member')

@section('styles')
    @include('admin.components.detail-styles')
@endsection

@section('content')
    @php
        $gym_member->loadMissing(['role', 'membershipPayments.membership']);

        $profilePicture = $gym_member->profile_picture
            ? asset($gym_member->profile_picture)
            : asset('assets/images/profile-45x45.png');
        $fullName = trim(($gym_member->first_name ?? '') . ' ' . ($gym_member->last_name ?? ''));
        $latestPayment = $gym_member->membershipPayments->sortByDesc('created_at')->first();
        $activePayment = $gym_member->membershipPayments
            ->where('isapproved', 1)
            ->sortByDesc('expiration_at')
            ->first();
        $membershipName = $activePayment?->membership?->name ?? ($latestPayment?->membership?->name ?? 'No membership');
        $expiresAt = $activePayment?->expiration_at ? \Carbon\Carbon::parse($activePayment->expiration_at) : null;
        $expiresText = $expiresAt ? $expiresAt->format('M d, Y') : '—';

        $statusText = 'No active membership';
        $statusClass = 'neutral';
        if ($latestPayment) {
            $statusText = match ((int) $latestPayment->isapproved) {
                1 => 'Approved',
                2 => 'Rejected',
                default => 'Pending approval',
            };
            $statusClass = match ((int) $latestPayment->isapproved) {
                1 => $expiresAt && $expiresAt->isPast() ? 'danger' : 'success',
                2 => 'danger',
                default => 'warning',
            };
            if ((int) $latestPayment->isapproved === 1 && $expiresAt) {
                $statusText = $expiresAt->isPast()
                    ? 'Expired ' . $expiresAt->format('M d, Y')
                    : 'Active until ' . $expiresAt->format('M d, Y');
            }
        }
    @endphp

    <div class="container-fluid">
        <div class="detail-hero my-4">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <img src="{{ $profilePicture }}" alt="Member photo" class="detail-avatar">
                <div class="flex-grow-1">
                    <div class="hero-label mb-1">Member profile</div>
                    <h2 class="hero-title mb-1">{{ $fullName ?: 'Member' }}</h2>
                    <div class="hero-subtitle">User code: {{ $gym_member->user_code ?? '—' }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="detail-pill">
                            <i class="fa-solid fa-id-badge"></i>
                            <span>{{ $gym_member->role->name ?? 'Member' }}</span>
                        </span>
                        <span class="detail-pill">
                            <i class="fa-solid fa-envelope-open"></i>
                            <span>{{ $gym_member->email ?? 'No email' }}</span>
                        </span>
                        <span class="detail-pill">
                            <i class="fa-solid fa-phone"></i>
                            <span>{{ $gym_member->phone_number ?? 'No phone' }}</span>
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="detail-badge {{ $statusClass }}">
                        <i class="fa-solid fa-ticket"></i>
                        {{ $statusText }}
                    </span>
                    <span class="detail-chip">
                        <span class="icon"><i class="fa-solid fa-calendar-check"></i></span>
                        {{ $membershipName }}
                    </span>
                </div>
            </div>
        </div>

        <div class="detail-stats-grid">
            <div class="detail-stat">
                <span class="label">Membership</span>
                <div class="value">{{ $membershipName }}</div>
                <div class="hint">{{ $expiresAt ? 'Expires ' . $expiresText : 'No active membership' }}</div>
            </div>
            <div class="detail-stat">
                <span class="label">Status</span>
                <div class="value">{{ $statusText }}</div>
                <div class="hint">
                    Last payment {{ optional($latestPayment?->created_at)->format('M d, Y') ?? '—' }}
                </div>
            </div>
            <div class="detail-stat">
                <span class="label">Contact</span>
                <div class="value">{{ $gym_member->email ?? 'No email' }}</div>
                <div class="hint">{{ $gym_member->phone_number ?? 'No phone' }}</div>
            </div>
            <div class="detail-stat">
                <span class="label">Profile</span>
                <div class="value">{{ $gym_member->role->name ?? 'Member' }}</div>
                <div class="hint">User code: {{ $gym_member->user_code ?? '—' }}</div>
            </div>
        </div>

        <div class="detail-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Key details</h5>
                <span class="text-muted detail-meta">Last updated {{ optional($gym_member->updated_at)->format('M d, Y') ?? '—' }}</span>
            </div>
            <div class="table-responsive">
                <table class="detail-table">
                    <tbody>
                        <tr>
                            <th scope="row">Member code</th>
                            <td>{{ $gym_member->user_code ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Role</th>
                            <td>{{ $gym_member->role->name ?? 'Member' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Membership</th>
                            <td>{{ $membershipName }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Status</th>
                            <td>{{ $statusText }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Expiration</th>
                            <td>{{ $expiresText }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Address</th>
                            <td>{{ $gym_member->address ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Email</th>
                            <td>{{ $gym_member->email ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Phone number</th>
                            <td>{{ $gym_member->phone_number ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Created</th>
                            <td>{{ optional($gym_member->created_at)->format('M d, Y g:i A') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">Updated</th>
                            <td>{{ optional($gym_member->updated_at)->format('M d, Y g:i A') ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @php
            $now = now();
        @endphp

        <div class="detail-card mt-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Enrolled classes</h5>
                    <div class="text-muted detail-meta">Classes this member is enrolled in.</div>
                </div>
            </div>

            <form action="{{ route('admin.gym-management.members.view', $gym_member->id) }}" method="GET" class="row g-2 align-items-center mb-3">
                <div class="col-md-6 col-lg-4">
                    <label for="search" class="visually-hidden">Search enrolled classes</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control"
                            value="{{ $search }}"
                            placeholder="Search by class, code, or trainer"
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
                        <a href="{{ route('admin.gym-management.members.view', $gym_member->id) }}" class="btn btn-link text-decoration-none text-muted">
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
                            <th>Class</th>
                            <th>Trainer</th>
                            <th>Schedule</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($userSchedules as $index => $userSchedule)
                            @php
                                $class = $userSchedule->schedule;
                                $className = $class->name ?? 'Class removed';
                                $classCode = $class->class_code ?? '—';
                                $trainer = optional($class)->user;
                                $trainerName = $trainer ? trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? '')) : 'Not assigned';
                                $start = $class && $class->class_start_date ? \Carbon\Carbon::parse($class->class_start_date) : null;
                                $end = $class && $class->class_end_date ? \Carbon\Carbon::parse($class->class_end_date) : null;
                                $scheduleWindow = $start && $end
                                    ? $start->format('M d, Y g:i A') . ' — ' . $end->format('M d, Y g:i A')
                                    : ($start ? $start->format('M d, Y g:i A') : 'Not set');
                                $statusLabel = 'Not scheduled';
                                $badgeClass = 'bg-secondary';
                                if ($start && $end) {
                                    if ($now->lt($start)) {
                                        $statusLabel = 'Upcoming';
                                        $badgeClass = 'bg-warning text-dark';
                                    } elseif ($now->between($start, $end)) {
                                        $statusLabel = 'Ongoing';
                                        $badgeClass = 'bg-success';
                                    } else {
                                        $statusLabel = 'Completed';
                                        $badgeClass = 'bg-primary';
                                    }
                                } elseif ($start) {
                                    $statusLabel = $now->lt($start) ? 'Upcoming' : 'Completed';
                                    $badgeClass = $now->lt($start) ? 'bg-warning text-dark' : 'bg-secondary';
                                }
                                $joinedAt = optional($userSchedule->created_at)->format('M d, Y g:i A') ?? '—';
                            @endphp
                            <tr>
                                <td>{{ ($userSchedules->firstItem() ?? 0) + $index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $className }}</div>
                                    <div class="text-muted small">Code: {{ $classCode }}</div>
                                </td>
                                <td>
                                    <div>{{ $trainerName }}</div>
                                    @if($trainer)
                                        <div class="text-muted small">{{ $trainer->email ?? 'No email' }}</div>
                                    @endif
                                </td>
                                <td>{{ $scheduleWindow }}</td>
                                <td>
                                    <span class="badge {{ $badgeClass }} px-3 py-2">{{ $statusLabel }}</span>
                                </td>
                                <td>{{ $joinedAt }}</td>
                                <td class="text-center">
                                    @if($class)
                                        <a href="{{ route('admin.gym-management.schedules.view', $class->id) }}" class="btn btn-outline-secondary btn-sm">
                                            View class
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No enrolled classes found{{ $search !== '' ? ' for this search.' : '.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $userSchedules->count() }} of {{ $userSchedules->total() }} classes
                </div>
                {{ $userSchedules->links() }}
            </div>
        </div>
    </div>
@endsection
