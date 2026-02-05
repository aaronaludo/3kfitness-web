@extends('layouts.admin')
@section('title', 'Reschedule Requests')

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

            $formatRequestTime = function ($time) {
                try {
                    return \Carbon\Carbon::parse($time)->format('g:i A');
                } catch (\Exception $e) {
                    return $time;
                }
            };
        @endphp

        <style>
            .pill-chip {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                justify-content: flex-start;
                padding: 6px 12px;
                border-radius: 8px;
                border: 1px solid var(--pill-border, #d5deec);
                background: var(--pill-bg, #f5f7fb);
                color: var(--pill-text, #1f2937);
                font-weight: 600;
                font-size: 0.85rem;
                letter-spacing: 0.01em;
                box-shadow: none;
            }
            .pill-chip::before {
                content: '';
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--pill-dot, #9ca3af);
                opacity: 0.9;
            }
            .pill-chip-warning {
                --pill-bg: #fff4e5;
                --pill-border: #f3d7a6;
                --pill-text: #7a4b00;
                --pill-dot: #e0a100;
            }
            .pill-chip-success {
                --pill-bg: #e8f6ef;
                --pill-border: #c5e5d5;
                --pill-text: #1f5133;
                --pill-dot: #2e8b57;
            }
            .pill-chip-danger {
                --pill-bg: #fbecec;
                --pill-border: #f0c4c2;
                --pill-text: #7b1c1c;
                --pill-dot: #c0392b;
            }
            .pill-chip-secondary {
                --pill-bg: #f1f3f5;
                --pill-border: #dee2e6;
                --pill-text: #6c757d;
                --pill-dot: #6c757d;
            }
        </style>

        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 mt-2">
                <div>
                    <h2 class="title mb-1">Reschedule requests</h2>
                    <p class="text-muted mb-0 small">Trainers can propose changes to specific sessions. Approving will update those sessions automatically.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.gym-management.schedules') }}" class="btn btn-danger">
                        <i class="fa-solid fa-calendar-days me-2"></i>Classes
                    </a>
                    <a href="{{ route('admin.history.reschedule-requests') }}" class="btn btn-danger">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i>History
                    </a>
                </div>
            </div>

            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
            </div>

            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Trainer cadence</span>
                                <h5 class="fw-semibold mb-1">Current requests</h5>
                                <p class="text-muted mb-0">Review proposed session changes and approve or reject.</p>
                            </div>
                            <div class="text-end d-flex flex-column align-items-end gap-2">
                                <div>
                                    <span class="pill-chip pill-chip-warning">Pending: {{ $pendingCount ?? 0 }}</span>
                                    <span class="pill-chip pill-chip-secondary ms-2">Resolved: {{ $resolvedCount }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Class</th>
                                        <th>Trainer</th>
                                        <th>User Code</th>
                                        <th>Target sessions</th>
                                        <th>Proposed slot</th>
                                        <th>Notes</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rescheduleRequests as $requestItem)
                                        @php
                                            $statusMap = [
                                                0 => ['label' => 'Pending', 'class' => 'pill-chip pill-chip-warning'],
                                                1 => ['label' => 'Approved', 'class' => 'pill-chip pill-chip-success'],
                                                2 => ['label' => 'Rejected', 'class' => 'pill-chip pill-chip-danger'],
                                            ];
                                            $statusMeta = $statusMap[$requestItem->status] ?? $statusMap[0];
                                            $classItem = $requestItem->schedule;
                                            $trainer = $requestItem->trainer;
                                            $trainerIsArchived = (int) (optional($trainer)->is_archive ?? 0) === 1;
                                            $trainerDisplay = $trainerIsArchived
                                                ? ''
                                                : ($trainer ? ($trainer->first_name . ' ' . $trainer->last_name) : 'Trainer');
                                            $trainerCodeDisplay = ($trainer && ! $trainerIsArchived)
                                                ? ($trainer->user_code ?? '—')
                                                : '—';
                                            $dayList = collect($requestItem->recurring_days ?? [])->map(function ($d) use ($weekdayLookup) {
                                                return $weekdayLookup[$d] ?? ucfirst($d);
                                            })->implode(', ');
                                            $seriesRange = $requestItem->proposed_series_start_date && $requestItem->proposed_series_end_date
                                                ? $requestItem->proposed_series_start_date->format('F j, Y') . ' → ' . $requestItem->proposed_series_end_date->format('F j, Y')
                                                : 'Keep existing';
                                            $targetDates = collect($requestItem->target_session_dates ?? []);
                                            $proposedDates = collect($requestItem->proposed_session_dates ?? []);
                                            $targetDatesLabel = $targetDates->map(function ($date) {
                                                try {
                                                    return \Carbon\Carbon::parse($date)->format('F j, Y');
                                                } catch (\Exception $e) {
                                                    return $date;
                                                }
                                            })->implode(', ');
                                            $proposedDatesLabel = $proposedDates->map(function ($date) {
                                                try {
                                                    return \Carbon\Carbon::parse($date)->format('F j, Y');
                                                } catch (\Exception $e) {
                                                    return $date;
                                                }
                                            })->implode(', ');
                                            $targetSummary = $targetDates->count()
                                                ? $targetDates->count() . ' selected'
                                                : 'No sessions';
                                            $proposedSummary = $proposedDates->count()
                                                ? $proposedDates->count() . ' new date(s)'
                                                : 'Same dates';
                                            $timeWindowLabel = $formatRequestTime($requestItem->proposed_start_time) . ' - ' . $formatRequestTime($requestItem->proposed_end_time);
                                        @endphp
                                        <tr>
                                            <td>{{ $requestItem->id }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $classItem->name ?? 'Class #' . $requestItem->schedule_id }}</div>
                                                <div class="text-muted small">{{ $classItem->class_code ?? '' }}</div>
                                            </td>
                                            <td>
                                                {{ $trainerDisplay }}
                                                @if($trainer && $trainer->email && ! $trainerIsArchived)
                                                    <div class="text-muted small">{{ $trainer->email }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-muted small">{{ $trainerCodeDisplay }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $targetSummary }}</div>
                                                <div class="text-muted small">{{ $targetDatesLabel ?: '—' }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $proposedSummary }}</div>
                                                <div class="text-muted small">{{ $proposedDatesLabel ?: 'Same dates as selected' }}</div>
                                                <div class="text-muted small">{{ $timeWindowLabel }}</div>
                                                <div class="text-muted small">Requested {{ $requestItem->created_at ? $requestItem->created_at->format('F j, Y g:iA') : '' }}</div>
                                            </td>
                                            <td class="text-muted">
                                                {{ $requestItem->notes ?: '—' }}
                                            </td>
                                            <td>
                                                <span class="{{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                                                @if($requestItem->responded_at)
                                                    <div class="text-muted small mt-1">Handled {{ $requestItem->responded_at->format('F j, Y') }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if((int) $requestItem->status === 0)
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-success"
                                                            data-resched-action
                                                            data-mode="approve"
                                                            data-id="{{ $requestItem->id }}"
                                                        >
                                                            Approve
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger"
                                                            data-resched-action
                                                            data-mode="reject"
                                                            data-id="{{ $requestItem->id }}"
                                                        >
                                                            Reject
                                                        </button>
                                                    </div>
                                                @elseif((int) $requestItem->status === 1)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.gym-management.schedules.reschedules.destroy', $requestItem->id) }}"
                                                        onsubmit="return confirm('Delete this approved request?')"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="redirect_to" value="{{ route('admin.gym-management.schedules.reschedule-requests') }}">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted small">No action needed</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr id="reschedule-inline-{{ $requestItem->id }}" class="resched-inline d-none">
                                            <td colspan="9">
                                                <div class="border rounded-4 p-3 bg-light-subtle">
                                                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="badge bg-success bg-opacity-10 text-success rounded-circle p-3">
                                                                <i class="fa-solid fa-calendar-check"></i>
                                                            </div>
                                                            <div>
                                                                <p class="text-uppercase text-muted small mb-1 resched-inline-title">Approve reschedule</p>
                                                                <h6 class="mb-0 fw-semibold">{{ $classItem->name ?? 'Class' }} ({{ $classItem->class_code ?? '—' }})</h6>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary resched-inline-cancel" data-id="{{ $requestItem->id }}">Close</button>
                                                    </div>
                                                    <div class="row g-3 align-items-stretch mt-3">
                                                        <div class="col-12 col-md-5">
                                                            <div class="border rounded-4 p-3 h-100 bg-white">
                                                                <div class="d-flex align-items-start gap-3 mb-3">
                                                                    <div class="text-success fs-5">
                                                                        <i class="fa-solid fa-calendar-check"></i>
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <div class="fw-semibold">Selected sessions</div>
                                                                        <div class="text-muted small">{{ $targetDatesLabel ?: 'No sessions provided' }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex align-items-start gap-3 mb-3">
                                                                    <div class="text-success fs-5">
                                                                        <i class="fa-solid fa-clock"></i>
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <div class="fw-semibold">Time</div>
                                                                        <div class="text-muted small">{{ $timeWindowLabel }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex align-items-start gap-3">
                                                                    <div class="text-success fs-5">
                                                                        <i class="fa-solid fa-repeat"></i>
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <div class="fw-semibold">Move to</div>
                                                                        <div class="text-muted small">{{ $proposedDatesLabel ?: 'Same dates as selected' }}</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-7 d-flex flex-column">
                                                            <form method="POST" action="{{ route('admin.gym-management.schedules.reschedules.update', $requestItem->id) }}" class="d-flex flex-column h-100">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="redirect_to" value="{{ route('admin.gym-management.schedules.reschedule-requests') }}">
                                                                <input type="hidden" name="status" class="resched-status-input" value="1">
                                                                <label class="form-label fw-semibold resched-label">Internal comment (optional)</label>
                                                                <textarea class="form-control flex-grow-1" name="admin_comment" rows="6" placeholder="Add a note for the trainer or staff">{{ old('admin_comment') }}</textarea>
                                                                <div class="d-flex gap-2 justify-content-end mt-3">
                                                                    <button type="button" class="btn btn-light resched-inline-cancel" data-id="{{ $requestItem->id }}">Cancel</button>
                                                                    <button type="submit" class="btn btn-success resched-submit-btn">
                                                                        <i class="fa-solid fa-circle-check me-2"></i><span class="resched-submit-text">Approve request</span>
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                No reschedule requests yet. Trainers can request changes from the class detail screen.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{ $rescheduleRequests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inlineRows = document.querySelectorAll('.resched-inline');
            const inlineCancelButtons = document.querySelectorAll('.resched-inline-cancel');
            const actionButtons = document.querySelectorAll('[data-resched-action]');

            function hideAllInline() {
                inlineRows.forEach(function (row) {
                    row.classList.add('d-none');
                });
            }

            function updateInline(row, mode) {
                if (!row) return;
                const statusInput = row.querySelector('.resched-status-input');
                const title = row.querySelector('.resched-inline-title');
                const submitText = row.querySelector('.resched-submit-text');
                const submitBtn = row.querySelector('.resched-submit-btn');

                if (statusInput) {
                    statusInput.value = mode === 'reject' ? 2 : 1;
                }

                if (title) {
                    title.textContent = mode === 'reject' ? 'Reject reschedule' : 'Approve reschedule';
                }

                if (submitText) {
                    submitText.textContent = mode === 'reject' ? 'Reject request' : 'Approve request';
                }

                if (submitBtn) {
                    submitBtn.classList.remove('btn-success', 'btn-danger');
                    submitBtn.classList.add(mode === 'reject' ? 'btn-danger' : 'btn-success');
                }
            }

            actionButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = this.dataset.id;
                    const mode = this.dataset.mode || 'approve';
                    if (!id) return;
                    const row = document.getElementById(`reschedule-inline-${id}`);
                    if (!row) return;

                    hideAllInline();
                    updateInline(row, mode);
                    row.classList.remove('d-none');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            });

            inlineCancelButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    hideAllInline();
                });
            });
        });
    </script>
@endsection
