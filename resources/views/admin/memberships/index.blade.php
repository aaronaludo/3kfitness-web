@extends('layouts.admin')
@section('title', 'Memberships')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Memberships</h2></div>
                <div class="d-flex align-items-center">
                    <a class="btn btn-danger" href="{{ route('admin.staff-account-management.memberships.create') }}"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;&nbsp;Add</a>
                    <form action="{{ route('admin.staff-account-management.memberships.print') }}" method="POST" id="print-form">
                        @csrf
                        <input type="hidden" name="created_start" value="{{ request('start_date') }}">
                        <input type="hidden" name="created_end" value="{{ request('end_date') }}">
                        <input type="hidden" name="name" value="{{ request('name') }}">
                        <input type="hidden" name="search_column" value="{{ request('search_column') }}">
                        <input type="hidden" name="membership_status" value="{{ request('membership_status', 'all') }}">
                        <button class="btn btn-danger ms-2" type="submit" id="print-submit-button">
                            <i class="fa-solid fa-print"></i>&nbsp;&nbsp;&nbsp;
                            <span id="print-loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                </div>
            </div>
            @php
                $statusFilter = request('membership_status', 'all');
                $statusTallies = $statusTallies ?? [];
                $statusOptions = [
                    'all' => [
                        'label' => 'All plans',
                        'count' => $statusTallies['all'] ?? null,
                    ],
                    'active' => [
                        'label' => 'Active members',
                        'count' => $statusTallies['active'] ?? null,
                    ],
                    'empty' => [
                        'label' => 'No members yet',
                        'count' => $statusTallies['empty'] ?? null,
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
                                <h4 class="fw-semibold mb-1">Membership performance</h4>
                                <p class="text-muted mb-0">Review engagements at a glance or drill down with advanced filters.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">Showing {{ $data->total() }} results</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.staff-account-management.memberships') }}" method="GET" id="membership-filter-form" class="mt-4">
                            <input type="hidden" name="membership_status" id="membership-status-filter" value="{{ $statusFilter }}">

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @foreach ($statusOptions as $key => $option)
                                        <button
                                            type="button"
                                            class="membership-chip btn btn-sm rounded-pill px-3 {{ $statusFilter === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary' }}"
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
                                                placeholder="Search memberships"
                                                value="{{ request('name') }}"
                                                aria-label="Search memberships"
                                            />
                                        </div>
                                    </div>

                                    <a href="{{ route('admin.staff-account-management.memberships') }}" class="btn btn-link text-decoration-none text-muted px-0">Reset</a>

                                    <button
                                        class="btn btn-outline-secondary rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#membership-advanced-filters"
                                        aria-expanded="{{ $advancedFiltersOpen ? 'true' : 'false' }}"
                                        aria-controls="membership-advanced-filters"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="collapse border-top mt-4 pt-4{{ $advancedFiltersOpen ? ' show' : '' }}" id="membership-advanced-filters">
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
                                            <option value="name" {{ request('search_column') == 'name' ? 'selected' : '' }}>Name</option>
                                            <option value="month" {{ request('search_column') == 'month' ? 'selected' : '' }}>Month</option>
                                            <option value="members_approved" {{ request('search_column') == 'members_approved' ? 'selected' : '' }}>Total Members Approved</option>
                                            <option value="members_pending" {{ request('search_column') == 'members_pending' ? 'selected' : '' }}>Total Members Pending</option>
                                            <option value="members_reject" {{ request('search_column') == 'members_reject' ? 'selected' : '' }}>Total Members Reject</option>
                                            <option value="created_at" {{ request('search_column') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                            <option value="updated_at" {{ request('search_column') == 'updated_at' ? 'selected' : '' }}>Updated Date</option>
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
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
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
                                            <th class="sortable" data-column="name">Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="price">Price <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="month">Month <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="total_members_approved">Total Members Approved <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="total_members_pending">Total Members Pending <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="total_members_reject">Total Members Reject <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="updated_date">Updated Date <i class="fa fa-sort"></i></th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($data as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ $item->price }}</td>
                                                <td>{{ $item->month ?? '0' }}</td>
                                                <td>{{ $item->members_approved }}</td>
                                                <td>{{ $item->members_pending }}</td>
                                                <td>{{ $item->members_reject }}</td>
                                                <td>{{ $item->created_at }}</td>
                                                <td>{{ $item->updated_at }}</td>
                                                <td>
                                                    <div class="d-flex">
                                                        <div class="action-button"><a href="{{ route('admin.staff-account-management.memberships.view', $item->id) }}" title="View"><i class="fa-solid fa-eye"></i></a></div>
                                                        <div class="action-button"><a href="{{ route('admin.staff-account-management.memberships.edit', $item->id) }}" title="Edit"><i class="fa-solid fa-pencil text-primary"></i></a></div>
                                                        <div class="action-button">
                                                            <!--<form action="{{ route('admin.staff-account-management.memberships.delete') }}" method="POST" style="display: inline;">-->
                                                            <!--    @csrf-->
                                                            <!--    @method('DELETE')-->
                                                            <!--    <input type="hidden" name="id" value="{{ $item->id }}">-->
                                                            <!--    <button type="submit" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">-->
                                                            <!--        <i class="fa-solid fa-trash text-danger"></i>-->
                                                            <!--    </button>-->
                                                            <!--</form>-->
                                                            <button type="button" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $item->id }}" data-id="{{ $item->id }}" title="Delete" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                                <i class="fa-solid fa-trash text-danger"></i>
                                                            </button>
                                                        </div> 
                                                    </div>
                                                </td>
                                            </tr>
                                            <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="rejectModalLabel">Are you sure you want to delete ({{ $item->name }})?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.staff-account-management.memberships.delete') }}" method="POST" id="main-form-{{ $item->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                                            <div class="modal-body">
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <!--<button type="submit" class="btn btn-danger">Submit</button>-->
                                                                <button class="btn btn-danger" type="submit" id="submitButton-{{ $item->id }}">
                                                                    <span id="loader-{{ $item->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Submit
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                                document.getElementById('main-form-{{ $item->id }}').addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('submitButton-{{ $item->id }}');
                                                    const loader = document.getElementById('loader-{{ $item->id }}');
                                        
                                                    // Disable the button and show loader
                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
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
        const form = document.getElementById('membership-filter-form');
        if (!form) {
            return;
        }

        const statusInput = document.getElementById('membership-status-filter');
        const chipButtons = form.querySelectorAll('.membership-chip');
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

        chipButtons.forEach(function (chip) {
            chip.addEventListener('click', function () {
                const selectedStatus = this.dataset.status;
                statusInput.value = selectedStatus;

                chipButtons.forEach(function (btn) {
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
