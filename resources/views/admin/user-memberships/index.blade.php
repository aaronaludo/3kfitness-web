@extends('layouts.admin')
@section('title', 'User Memberships')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">User Memberships</h2></div>
                <div class="d-flex align-items-center">
                    <form action="{{ route('admin.staff-account-management.user-memberships.print') }}" method="POST" id="print-form">
                        @csrf
                        <input type="hidden" name="created_start" value="{{ request('start_date') }}">
                        <input type="hidden" name="created_end" value="{{ request('end_date') }}">
                        <input type="hidden" name="name" value="{{ request('name', request('member_name')) }}">
                        <input type="hidden" name="search_column" value="{{ request('search_column') }}">
                        <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                        <button class="btn btn-danger ms-2" type="submit" id="print-submit-button">
                            <i class="fa-solid fa-print"></i>&nbsp;&nbsp;&nbsp;
                            <span id="print-loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                </div>
            </div>
            @php
                $statusFilter = request('status', 'all');
                $statusTallies = $statusTallies ?? [];
                $statusOptions = [
                    'all' => [
                        'label' => 'All memberships',
                        'count' => $statusTallies['all'] ?? null,
                    ],
                    'pending' => [
                        'label' => 'Pending',
                        'count' => $statusTallies['pending'] ?? null,
                    ],
                    'approved' => [
                        'label' => 'Approved',
                        'count' => $statusTallies['approved'] ?? null,
                    ],
                    'rejected' => [
                        'label' => 'Rejected',
                        'count' => $statusTallies['rejected'] ?? null,
                    ],
                ];
                $advancedFiltersOpen = request()->filled('search_column') || request()->filled('start_date') || request()->filled('end_date');
            @endphp

            <div class="col-12 mb-20">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Overview</span>
                                <h4 class="fw-semibold mb-1">User memberships</h4>
                                <p class="text-muted mb-0">Quickly spot pending approvals or focus on recent renewals using the filters below.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">Showing {{ $data->total() }} results</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.staff-account-management.user-memberships') }}" method="GET" id="user-membership-filter-form" class="mt-4">
                            <input type="hidden" name="status" id="user-membership-status-filter" value="{{ $statusFilter }}">

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @foreach ($statusOptions as $key => $option)
                                        <button
                                            type="button"
                                            class="status-chip btn btn-sm rounded-pill px-3 {{ $statusFilter === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary' }}"
                                            data-status="{{ $key }}"
                                        >
                                            {{ $option['label'] }}
                                            @if(!is_null($option['count']))
                                                <span class="badge bg-transparent text-muted fw-semibold ms-2">{{ $option['count'] }}</span>
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
                                                name="name"
                                                placeholder="Search members or plans"
                                                value="{{ request('name', request('member_name')) }}"
                                                aria-label="Search user memberships"
                                            />
                                        </div>
                                    </div>

                                    <a href="{{ route('admin.staff-account-management.user-memberships') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>

                                    <button
                                        class="btn btn-outline-secondary rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#user-membership-advanced-filters"
                                        aria-expanded="{{ $advancedFiltersOpen ? 'true' : 'false' }}"
                                        aria-controls="user-membership-advanced-filters"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="collapse border-top mt-4 pt-4{{ $advancedFiltersOpen ? ' show' : '' }}" id="user-membership-advanced-filters">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <span class="text-muted text-uppercase small fw-semibold">Quick ranges</span>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill range-chip" data-range="last-week">Last week</button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill range-chip" data-range="last-month">Last month</button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill range-chip" data-range="last-year">Last year</button>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label for="search-column" class="form-label text-muted text-uppercase small">Search by</label>
                                        <select id="search-column" name="search_column" class="form-select rounded-3">
                                            <option value="" {{ request('search_column') ? '' : 'selected' }}>Best match</option>
                                            <option value="id" {{ request('search_column') == 'id' ? 'selected' : '' }}>ID</option>
                                            <option value="member_name" {{ request('search_column', 'member_name') == 'member_name' ? 'selected' : '' }}>Member Name</option>
                                            <option value="membership" {{ request('search_column') == 'membership' ? 'selected' : '' }}>Membership</option>
                                            <option value="expiration_at" {{ request('search_column') == 'expiration_at' ? 'selected' : '' }}>Expiration Date</option>
                                            <option value="created_at" {{ request('search_column') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                            <option value="updated_at" {{ request('search_column') == 'updated_at' ? 'selected' : '' }}>Updated Date</option>
                                            <option value="status" {{ request('search_column') == 'status' ? 'selected' : '' }}>Status</option>
                                        </select>
                                    </div>

                                    <div class="col-lg-4">
                                        <label for="start-date" class="form-label text-muted text-uppercase small">Start date</label>
                                        <input
                                            type="date"
                                            id="start-date"
                                            class="form-control rounded-3"
                                            name="start_date"
                                            value="{{ request('start_date') }}"
                                        />
                                    </div>

                                    <div class="col-lg-4">
                                        <label for="end-date" class="form-label text-muted text-uppercase small">End date</label>
                                        <input
                                            type="date"
                                            id="end-date"
                                            class="form-control rounded-3"
                                            name="end_date"
                                            value="{{ request('end_date') }}"
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-column="id">ID <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="member_name">Member Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="membership">Membership <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="expiration_date">Expiration Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="updated_date">Updated Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="status">Status <i class="fa fa-sort"></i></th>
                                            <th>Classes Enrolled</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($data as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ $item->user->first_name }} {{ $item->user->last_name }}</td>
                                                <td>{{ $item->membership->name }}</td>
                                                <td>{{ $item->expiration_at }}</td>
                                                <td>{{ $item->created_at }}</td>
                                                <td>{{ $item->updated_at }}</td>
                                                <td>
                                                    @php
                                                        $statusMap = [
                                                            0 => ['label' => 'Pending',  'class' => 'bg-warning text-dark'],
                                                            1 => ['label' => 'Approved', 'class' => 'bg-success'],
                                                            2 => ['label' => 'Rejected', 'class' => 'bg-danger'],
                                                        ];
                                                        $s = $statusMap[$item->isapproved] ?? $statusMap[0];
                                                    @endphp
                                                
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge {{ $s['class'] }} px-3 py-2">
                                                            {{ $s['label'] }}
                                                        </span>
                                                
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#umStatusModal-{{ $item->id }}"
                                                            aria-label="Change Status"
                                                        >
                                                            Change
                                                        </button>
                                                    </div>
                                                
                                                    <!-- Status Change Modal -->
                                                    <div class="modal fade" id="umStatusModal-{{ $item->id }}" tabindex="-1" aria-labelledby="umStatusModalLabel-{{ $item->id }}" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <form
                                                                method="POST"
                                                                action="{{ route('admin.staff-account-management.user-memberships.isapprove') }}"
                                                                class="modal-content"
                                                                id="umStatusForm-{{ $item->id }}"
                                                                {{-- Optional: if you have a related count (e.g., active usages), pass it here. If not present, guard stays hidden. --}}
                                                                data-related-count="{{ $item->memberships_count ?? 0 }}"
                                                            >
                                                                @csrf
                                                                {{-- Keep POST to match your current route; add @method('PUT') if your route expects PUT --}}
                                                                {{-- @method('PUT') --}}
                                                
                                                                <input type="hidden" name="id" value="{{ $item->id }}">
                                                
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="umStatusModalLabel-{{ $item->id }}">Change Membership Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                
                                                                <div class="modal-body">
                                                                    {{-- Choices --}}
                                                                    <div class="mb-3">
                                                                        <label for="umStatusSelect-{{ $item->id }}" class="form-label fw-semibold">Select status</label>
                                                                        <select class="form-select" id="umStatusSelect-{{ $item->id }}" name="isapproved">
                                                                            <option value="0" {{ $item->isapproved == 0 ? 'selected' : '' }}>Pending</option>
                                                                            <option value="1" {{ $item->isapproved == 1 ? 'selected' : '' }}>Approve</option>
                                                                            <option value="2" {{ $item->isapproved == 2 ? 'selected' : '' }}>Reject</option>
                                                                        </select>
                                                                    </div>
                                                
                                                                    {{-- Conditional warning for Reject when there are related records --}}
                                                                    <div id="umRejectGuard-{{ $item->id }}" class="border rounded p-3 bg-light d-none">
                                                                        <div class="d-flex align-items-start gap-2">
                                                                            <i class="fa-solid fa-triangle-exclamation text-danger mt-1"></i>
                                                                            <div>
                                                                                <div class="fw-semibold text-danger mb-1">Heads up before rejecting</div>
                                                                                <div class="small text-muted">
                                                                                    This membership may have linked records (e.g., usage, payments, or sessions).
                                                                                    Rejecting could impact users. Please confirm you understand before proceeding.
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="form-check mt-3">
                                                                            <input class="form-check-input" type="checkbox" value="1" id="umRejectConfirm-{{ $item->id }}">
                                                                            <label class="form-check-label" for="umRejectConfirm-{{ $item->id }}">
                                                                                I understand the impact of rejecting.
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                        Cancel
                                                                    </button>
                                                                    <button type="submit" class="btn btn-primary" id="umSaveStatusBtn-{{ $item->id }}">
                                                                        Save
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                
                                                    <script>
                                                    (function() {
                                                        const form     = document.getElementById('umStatusForm-{{ $item->id }}');
                                                        const select   = document.getElementById('umStatusSelect-{{ $item->id }}');
                                                        const guardBox = document.getElementById('umRejectGuard-{{ $item->id }}');
                                                        const confirmC = document.getElementById('umRejectConfirm-{{ $item->id }}');
                                                        const saveBtn  = document.getElementById('umSaveStatusBtn-{{ $item->id }}');
                                                
                                                        // Interpret any positive integer as "has related records"
                                                        const relatedCount = parseInt(form.dataset.relatedCount, 10);
                                                        const hasRelated   = Number.isFinite(relatedCount) && relatedCount > 0;
                                                
                                                        function updateGuard() {
                                                            const rejectChosen = select.value === '2';
                                                            if (hasRelated && rejectChosen) {
                                                                guardBox.classList.remove('d-none');
                                                                saveBtn.disabled = !confirmC.checked;
                                                            } else {
                                                                guardBox.classList.add('d-none');
                                                                saveBtn.disabled = false;
                                                                if (confirmC) confirmC.checked = false;
                                                            }
                                                        }
                                                
                                                        // Init on open (Bootstrap modal event)
                                                        const modalEl = document.getElementById('umStatusModal-{{ $item->id }}');
                                                        modalEl.addEventListener('shown.bs.modal', updateGuard);
                                                
                                                        select.addEventListener('change', updateGuard);
                                                        if (confirmC) {
                                                            confirmC.addEventListener('change', () => {
                                                                if (select.value === '2' && hasRelated) {
                                                                    saveBtn.disabled = !confirmC.checked;
                                                                }
                                                            });
                                                        }
                                                    })();
                                                    </script>
                                                </td>                                                
                                                <td>
                                                    @php
                                                        $classes = collect(optional($item->user)->userSchedules)
                                                            ->map(function ($userSchedule) {
                                                                $schedule = $userSchedule->schedule;
                                                                if (!$schedule) {
                                                                    return null;
                                                                }
                                                                return [
                                                                    'id' => $schedule->id,
                                                                    'name' => $schedule->name,
                                                                ];
                                                            })
                                                            ->filter()
                                                            ->unique('id')
                                                            ->values();
                                                    @endphp
                                                    @if($classes->isNotEmpty())
                                                        @foreach($classes as $class)
                                                            <a
                                                                href="{{ route('admin.gym-management.schedules.view', $class['id']) }}"
                                                                class="text-decoration-none"
                                                            >
                                                                {{ $class['name'] }}
                                                            </a>@if(!$loop->last), @endif
                                                        @endforeach
                                                    @else
                                                        <span class="text-muted">No classes enrolled</span>
                                                    @endif
                                                </td>
                                                <td>{{ $item->created_by }}</td>
                                                <td>
                                                    <div class="d-flex">
                                                        <div class="action-button"><a href="{{ route('admin.staff-account-management.user-memberships.view', $item->id) }}" title="View"><i class="fa-solid fa-eye"></i></a></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                {{ $data->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('user-membership-filter-form');
            if (!form) {
                return;
            }

            const statusInput = document.getElementById('user-membership-status-filter');
            const statusChips = form.querySelectorAll('.status-chip');
            const rangeButtons = form.querySelectorAll('.range-chip');
            const startInput = document.getElementById('start-date');
            const endInput = document.getElementById('end-date');

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

            statusChips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    const selected = this.dataset.status;
                    statusInput.value = selected;

                    statusChips.forEach(function (btn) {
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
