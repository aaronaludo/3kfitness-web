@extends('layouts.admin')
@section('title', 'Reschedule Requests History')

@section('content')
    <div class="container-fluid">
        @php
            $weekdayLookup = [
                'sun' => 'Sunday',
                'mon' => 'Monday',
                'tue' => 'Tuesday',
                'wed' => 'Wednesday',
                'thu' => 'Thursday',
                'fri' => 'Friday',
                'sat' => 'Saturday',
            ];

            $statusFilter = $filters['status'] ?? 'resolved';
            $statusOptions = [
                'resolved' => ['label' => 'Resolved', 'class' => 'bg-secondary'],
                'approved' => ['label' => 'Approved', 'class' => 'bg-success'],
                'rejected' => ['label' => 'Rejected', 'class' => 'bg-danger'],
                'pending' => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                'all' => ['label' => 'All', 'class' => 'bg-dark'],
            ];

            $hasFilters = ($filters['search'] ?? '') !== ''
                || ($filters['trainer_id'] ?? null)
                || ($filters['class_id'] ?? null)
                || ($filters['start_date'] ?? null)
                || ($filters['end_date'] ?? null)
                || ($statusFilter !== 'resolved');

            $formatTime = function ($time) {
                try {
                    return \Carbon\Carbon::parse($time)->format('g:i A');
                } catch (\Exception $e) {
                    return $time;
                }
            };
        @endphp

        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="title mb-1">Reschedule Request History</h2>
                    <p class="text-muted mb-0 small">Approved and rejected cadence changes from trainers.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.gym-management.schedules') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-calendar-days me-2"></i>Current requests
                    </a>
                </div>
            </div>

            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">History</span>
                                <h4 class="fw-semibold mb-1">Trainer reschedule requests</h4>
                                <p class="text-muted mb-0">
                                    Filter by class, trainer, request window, or decision status. Default view shows resolved requests.
                                </p>
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Requests</div>
                                    <div class="fs-5 fw-semibold">{{ number_format($stats['total'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Approved</div>
                                    <div class="fs-5 fw-semibold text-success">{{ number_format($stats['approved'] ?? 0) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-semibold">Rejected</div>
                                    <div class="fs-5 fw-semibold text-danger">{{ number_format($stats['rejected'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>

                        <form
                            action="{{ route('admin.history.reschedule-requests') }}"
                            method="GET"
                            id="reschedule-history-filter-form"
                            class="mt-3"
                        >
                            <input type="hidden" name="status" id="reschedule-history-status" value="{{ $statusFilter }}">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                                @foreach ($statusOptions as $key => $meta)
                                    @php $count = $statusTallies[$key] ?? null; @endphp
                                    <button
                                        type="button"
                                        class="btn btn-sm rounded-pill px-3 reschedule-history-status-chip {{ $statusFilter === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                        data-status="{{ $key }}"
                                        aria-label="Filter requests by {{ strtolower($meta['label']) }}"
                                    >
                                        {{ $meta['label'] }}
                                        @if(!is_null($count))
                                            <span class="badge bg-transparent {{ $statusFilter === $key ? 'text-white' : 'text-dark' }} fw-semibold ms-2">{{ $count }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>

                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label text-muted text-uppercase small mb-1" for="reschedule-search">Search</label>
                                    <input
                                        type="search"
                                        class="form-control"
                                        id="reschedule-search"
                                        name="search"
                                        placeholder="Class, trainer, notes, admin"
                                        value="{{ $filters['search'] ?? '' }}"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted text-uppercase small mb-1" for="reschedule-class">Class</label>
                                    <select id="reschedule-class" name="class_id" class="form-select">
                                        <option value="">All classes</option>
                                        @foreach ($classOptions as $class)
                                            <option
                                                value="{{ $class->id }}"
                                                {{ (int) ($filters['class_id'] ?? 0) === (int) $class->id ? 'selected' : '' }}
                                            >
                                                {{ $class->name }} @if($class->class_code) ({{ $class->class_code }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted text-uppercase small mb-1" for="reschedule-trainer">Trainer</label>
                                    <select id="reschedule-trainer" name="trainer_id" class="form-select">
                                        <option value="">All trainers</option>
                                        @foreach ($trainerOptions as $trainer)
                                            <option
                                                value="{{ $trainer->id }}"
                                                {{ (int) ($filters['trainer_id'] ?? 0) === (int) $trainer->id ? 'selected' : '' }}
                                            >
                                                {{ $trainer->first_name }} {{ $trainer->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted text-uppercase small mb-1" for="reschedule-start">Start date</label>
                                    <input
                                        type="date"
                                        id="reschedule-start"
                                        name="start_date"
                                        class="form-control"
                                        value="{{ $filters['start_date'] ?? '' }}"
                                    />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted text-uppercase small mb-1" for="reschedule-end">End date</label>
                                    <input
                                        type="date"
                                        id="reschedule-end"
                                        name="end_date"
                                        class="form-control"
                                        value="{{ $filters['end_date'] ?? '' }}"
                                    />
                                </div>
                                <div class="col-md-12 d-flex gap-2 mt-2">
                                    <a href="{{ route('admin.history.reschedule-requests') }}" class="btn btn-link text-decoration-none text-muted px-0 {{ $hasFilters ? '' : 'disabled' }}">
                                        Reset
                                    </a>
                                    <button type="submit" class="btn btn-danger px-4">
                                        <i class="fa-solid fa-magnifying-glass me-2"></i>Apply
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive mt-3">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Class</th>
                                        <th>Trainer</th>
                                        <th>Cadence</th>
                                        <th>Series window</th>
                                        <th>Notes</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rescheduleRequests as $requestItem)
                                        @php
                                            $statusMap = [
                                                0 => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                                                1 => ['label' => 'Approved', 'class' => 'bg-success'],
                                                2 => ['label' => 'Rejected', 'class' => 'bg-danger'],
                                            ];
                                            $statusMeta = $statusMap[$requestItem->status] ?? $statusMap[0];
                                            $classItem = $requestItem->schedule;
                                            $trainer = $requestItem->trainer;
                                            $responder = $requestItem->responder;
                                            $dayList = collect($requestItem->recurring_days ?? [])->map(function ($d) use ($weekdayLookup) {
                                                return $weekdayLookup[$d] ?? ucfirst($d);
                                            })->implode(', ');
                                            $seriesRange = $requestItem->proposed_series_start_date && $requestItem->proposed_series_end_date
                                                ? $requestItem->proposed_series_start_date->format('M j, Y') . ' → ' . $requestItem->proposed_series_end_date->format('M j, Y')
                                                : 'Keep existing';
                                        @endphp
                                        <tr>
                                            <td>{{ $requestItem->id }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $classItem->name ?? 'Class #' . $requestItem->schedule_id }}</div>
                                                <div class="text-muted small">{{ $classItem->class_code ?? '' }}</div>
                                            </td>
                                            <td>
                                                {{ $trainer ? ($trainer->first_name . ' ' . $trainer->last_name) : 'Trainer' }}
                                                @if($trainer && $trainer->email)
                                                    <div class="text-muted small">{{ $trainer->email }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $dayList ?: '—' }}</div>
                                                <div class="text-muted small">{{ $formatTime($requestItem->proposed_start_time) }} - {{ $formatTime($requestItem->proposed_end_time) }}</div>
                                            </td>
                                            <td>
                                                <div>{{ $seriesRange }}</div>
                                                <div class="text-muted small">Requested {{ $requestItem->created_at ? $requestItem->created_at->format('M j, Y g:i A') : '' }}</div>
                                                @if($requestItem->responded_at)
                                                    <div class="text-muted small">Handled {{ $requestItem->responded_at->format('M j, Y g:i A') }}</div>
                                                @endif
                                            </td>
                                            <td class="text-muted">
                                                <div>{{ $requestItem->notes ?: '—' }}</div>
                                                @if($requestItem->admin_comment)
                                                    <div class="small text-dark mt-1">Admin: {{ $requestItem->admin_comment }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $statusMeta['class'] }} px-3 py-2">{{ $statusMeta['label'] }}</span>
                                                @if($responder)
                                                    <div class="text-muted small mt-1">By {{ $responder->first_name }} {{ $responder->last_name }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                No reschedule requests found for the selected filters.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{ $rescheduleRequests->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('reschedule-history-filter-form');
            if (!form) return;

            const statusInput = document.getElementById('reschedule-history-status');
            const chips = form.querySelectorAll('.reschedule-history-status-chip');

            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    const nextStatus = this.dataset.status || 'resolved';
                    statusInput.value = nextStatus;
                    form.submit();
                });
            });
        });
    </script>
@endsection
