@extends('layouts.admin')
@section('title', 'Feedbacks')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div><h2 class="title">Feedbacks</h2></div>
                <div>
                    <a href="{{ route('admin.feedbacks.create') }}" class="btn btn-danger">
                        <i class="fa-solid fa-plus"></i> Add Feedback
                    </a>
                </div>
            </div>

            <div class="col-12 mt-3">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            @php
                $statusFilter = $status ?? request('status', 'all');
                $statusTallies = $statusTallies ?? [];
                $statusOptions = [
                    'all' => [
                        'label' => 'All',
                        'count' => $statusTallies['all'] ?? null,
                    ],
                    'pending' => [
                        'label' => 'Pending',
                        'count' => $statusTallies['pending'] ?? null,
                    ],
                    'confirmed' => [
                        'label' => 'Confirmed',
                        'count' => $statusTallies['confirmed'] ?? null,
                    ],
                ];
                $advancedFiltersOpen = request()->filled('start_date') || request()->filled('end_date');
            @endphp

            <div class="col-12 mb-20">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Overview</span>
                                <h4 class="fw-semibold mb-1">Member feedback</h4>
                                <p class="text-muted mb-0">Review and manage member feedback with quick filters.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">Showing {{ $feedbacks->total() }} results</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.feedbacks.index') }}" method="GET" id="feedback-filter-form" class="mt-4">
                            <input type="hidden" name="status" id="feedback-status-filter" value="{{ $statusFilter }}">

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
                                                name="search"
                                                placeholder="Search feedback"
                                                value="{{ $search ?? request('search') }}"
                                                aria-label="Search feedback"
                                            />
                                        </div>
                                    </div>

                                    <a
                                        href="{{ route('admin.feedbacks.index') }}"
                                        class="btn btn-link text-decoration-none text-muted px-0"
                                    >
                                        Reset
                                    </a>

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#feedbackFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="feedbackFiltersModal" tabindex="-1" aria-labelledby="feedbackFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="feedbackFiltersModalLabel">Advanced filters</h5>
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
                                                            <label for="feedback-start-date" class="form-label small text-muted mb-1">Start date</label>
                                                            <input
                                                                type="date"
                                                                id="feedback-start-date"
                                                                name="start_date"
                                                                class="form-control rounded-3"
                                                                value="{{ request('start_date') }}"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label for="feedback-end-date" class="form-label small text-muted mb-1">End date</label>
                                                            <input
                                                                type="date"
                                                                id="feedback-end-date"
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
                <div class="box">
                    <div class="table-responsive mb-3">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Member</th>
                                    <th>Title</th>
                                    <th>Message</th>
                                    <th>Stars</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($feedbacks as $item)
                                    @php
                                        $user = $item->user;
                                        $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';
                                        $memberName = $fullName !== '' ? $fullName : ($user->name ?? $user->email ?? 'Guest');
                                        $stars = (int) ($item->stars ?? 5);
                                        $stars = max(1, min(5, $stars));
                                    @endphp
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $memberName }}</td>
                                        <td>{{ $item->title }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($item->description, 80) }}</td>
                                        <td>
                                            <span class="text-warning">{{ str_repeat('★', $stars) }}</span>
                                            <span class="text-muted">{{ str_repeat('☆', 5 - $stars) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $item->admin_confirmation_status ? 'bg-success' : 'bg-warning text-dark' }}">
                                                {{ $item->admin_confirmation_status ? 'Confirmed' : 'Pending' }}
                                            </span>
                                        </td>
                                        <td>{{ optional($item->created_at)->format('F j, Y g:iA') }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <div class="action-button">
                                                    <a href="{{ route('admin.feedbacks.show', $item) }}" title="View">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                </div>
                                                <div class="action-button">
                                                    <a href="{{ route('admin.feedbacks.edit', $item) }}" title="Edit">
                                                        <i class="fa-solid fa-pen text-primary"></i>
                                                    </a>
                                                </div>
                                                @if(!$item->admin_confirmation_status)
                                                    <div class="action-button">
                                                        <button
                                                            type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#feedbackConfirmModal-{{ $item->id }}"
                                                            data-id="{{ $item->id }}"
                                                            title="Confirm"
                                                            style="background: none; border: none; padding: 0; cursor: pointer;"
                                                        >
                                                            <i class="fa-solid fa-circle-check text-success"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                                <div class="action-button">
                                                    <button
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#feedbackDeleteModal-{{ $item->id }}"
                                                        data-id="{{ $item->id }}"
                                                        title="Delete"
                                                        style="background: none; border: none; padding: 0; cursor: pointer;"
                                                    >
                                                        <i class="fa-solid fa-trash text-danger"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @if(!$item->admin_confirmation_status)
                                        <div class="modal fade" id="feedbackConfirmModal-{{ $item->id }}" tabindex="-1" aria-labelledby="feedbackConfirmModalLabel-{{ $item->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow rounded-4">
                                                    <div class="modal-header border-0 pb-0">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="badge bg-success bg-opacity-10 text-success rounded-circle p-3">
                                                                <i class="fa-solid fa-circle-check"></i>
                                                            </div>
                                                            <div>
                                                                <p class="text-uppercase text-muted small mb-1">Confirm feedback</p>
                                                                <h5 class="fw-semibold mb-0" id="feedbackConfirmModalLabel-{{ $item->id }}">
                                                                    {{ $item->title ?? 'Feedback' }}
                                                                </h5>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('admin.feedbacks.confirm', $item) }}" method="POST" id="feedback-confirm-form-{{ $item->id }}">
                                                        @csrf
                                                        <div class="modal-body pt-3">
                                                            <div class="alert alert-success bg-opacity-10 text-success border-0 rounded-3">
                                                                Confirming will publish this feedback on the landing testimonials.
                                                            </div>
                                                            <label class="form-label fw-semibold mt-2">Confirm with your password</label>
                                                            <div class="input-group">
                                                                <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                            <button class="btn btn-success" type="submit" id="feedback-confirm-submit-{{ $item->id }}">
                                                                <span id="feedback-confirm-loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                Confirm feedback
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <script>
                                            document.getElementById('feedback-confirm-form-{{ $item->id }}')?.addEventListener('submit', function () {
                                                const submitButton = document.getElementById('feedback-confirm-submit-{{ $item->id }}');
                                                const loader = document.getElementById('feedback-confirm-loader-{{ $item->id }}');

                                                if (submitButton) submitButton.disabled = true;
                                                if (loader) loader.classList.remove('d-none');
                                            });
                                        </script>
                                    @endif
                                    <div class="modal fade" id="feedbackDeleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="feedbackDeleteModalLabel-{{ $item->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow rounded-4">
                                                <div class="modal-header border-0 pb-0">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="badge bg-danger bg-opacity-10 text-danger rounded-circle p-3">
                                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                                        </div>
                                                        <div>
                                                            <p class="text-uppercase text-muted small mb-1">Delete feedback</p>
                                                            <h5 class="fw-semibold mb-0" id="feedbackDeleteModalLabel-{{ $item->id }}">
                                                                {{ $item->title ?? 'Feedback' }}
                                                            </h5>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('admin.feedbacks.destroy', $item) }}" method="POST" id="feedback-delete-form-{{ $item->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-body pt-3">
                                                        <div class="alert alert-danger bg-opacity-10 text-danger border-0 rounded-3">
                                                            Deleting will permanently remove this feedback. This action cannot be undone.
                                                        </div>
                                                        <label class="form-label fw-semibold mt-2">Confirm with your password</label>
                                                        <div class="input-group">
                                                            <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                            <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button class="btn btn-danger" type="submit" id="feedback-delete-submit-{{ $item->id }}">
                                                            <span id="feedback-delete-loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                            Delete feedback
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <script>
                                        document.getElementById('feedback-delete-form-{{ $item->id }}')?.addEventListener('submit', function () {
                                            const submitButton = document.getElementById('feedback-delete-submit-{{ $item->id }}');
                                            const loader = document.getElementById('feedback-delete-loader-{{ $item->id }}');

                                            if (submitButton) submitButton.disabled = true;
                                            if (loader) loader.classList.remove('d-none');
                                        });
                                    </script>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No feedback found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                        {{ $feedbacks->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('feedback-filter-form');
            if (!form) {
                return;
            }

            const statusInput = document.getElementById('feedback-status-filter');
            const chipButtons = form.querySelectorAll('.status-chip');
            const rangeButtons = form.querySelectorAll('.range-chip');
            const startInput = document.getElementById('feedback-start-date');
            const endInput = document.getElementById('feedback-end-date');

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
                    start.setDate(end.getDate() - 7);
                } else if (range === 'last-month') {
                    start.setMonth(end.getMonth() - 1);
                } else if (range === 'last-year') {
                    start.setFullYear(end.getFullYear() - 1);
                }

                if (startInput) startInput.value = formatDate(start);
                if (endInput) endInput.value = formatDate(end);
            }

            chipButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    if (statusInput) {
                        statusInput.value = button.dataset.status || 'all';
                    }
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            });

            rangeButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    applyRange(button.dataset.range);
                });
            });
        });
    </script>
@endsection
