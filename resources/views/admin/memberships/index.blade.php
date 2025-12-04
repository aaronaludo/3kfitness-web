@extends('layouts.admin')
@section('title', 'Memberships')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $showArchived = request()->boolean('show_archived');
                $printSource = $showArchived ? $archivedData : $data;
                $printAllSource = $showArchived ? ($printAllArchived ?? collect()) : ($printAllActive ?? collect());

                $mapMembership = function ($item) {
                    return [
                        'id' => $item->id ?? '—',
                        'name' => $item->name ?? '—',
                        'description' => $item->description ?? '',
                        'price' => $item->price ?? '0',
                        'month' => $item->month ?? '0',
                        'class_limit' => $item->class_limit ?? 'Unlimited',
                        'approved' => $item->members_approved ?? 0,
                        'pending' => $item->members_pending ?? 0,
                        'rejected' => $item->members_reject ?? 0,
                        'created' => optional($item->created_at)->format('M j, Y g:i A') ?? '',
                        'updated' => optional($item->updated_at)->format('M j, Y g:i A') ?? '',
                        'archived' => (int) $item->is_archive === 1 ? 'Archived' : 'Active',
                    ];
                };

                $printMemberships = collect($printSource->items() ?? [])->map($mapMembership)->values();
                $printAllMemberships = collect($printAllSource ?? [])->map($mapMembership)->values();

                $printPayload = [
                    'title' => $showArchived ? 'Archived memberships' : 'Membership performance',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => request('name'),
                        'search_column' => request('search_column'),
                        'membership_status' => request('membership_status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                    ],
                    'count' => $printMemberships->count(),
                    'items' => $printMemberships,
                ];

                $printAllPayload = [
                    'title' => $showArchived ? 'Archived memberships (all pages)' : 'Membership performance (all pages)',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => request('name'),
                        'search_column' => request('search_column'),
                        'membership_status' => request('membership_status', 'all') ?: 'all',
                        'start' => request('start_date'),
                        'end' => request('end_date'),
                        'show_archived' => $showArchived,
                        'scope' => 'all',
                    ],
                    'count' => $printAllMemberships->count(),
                    'items' => $printAllMemberships,
                ];
            @endphp
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
                        <button
                            class="btn btn-danger ms-2"
                            type="submit"
                            id="print-submit-button"
                            data-print='@json($printPayload)'
                            data-print-all='@json($printAllPayload)'
                            aria-label="Open printable/PDF view of filtered memberships"
                        >
                            <i class="fa-solid fa-print"></i>&nbsp;&nbsp;&nbsp;
                            <span id="print-loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.staff-account-management.memberships', request()->except(['show_archived', 'page', 'archive_page'])) }}"
                        >
                            <i class="fa-solid fa-rotate-left"></i>&nbsp;&nbsp;Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.staff-account-management.memberships', array_merge(request()->except(['page', 'archive_page']), ['show_archived' => 1])) }}"
                        >
                            <i class="fa-solid fa-box-archive"></i>&nbsp;&nbsp;View archived
                        </a>
                    @endif
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
                                <span class="d-block text-muted small">
                                    @if ($showArchived)
                                        Showing {{ $archivedData->total() }} archived memberships
                                    @else
                                        Showing {{ $data->total() }} results
                                    @endif
                                </span>
                            </div>
                        </div>

                        <form action="{{ route('admin.staff-account-management.memberships') }}" method="GET" id="membership-filter-form" class="mt-4">
                            <input type="hidden" name="membership_status" id="membership-status-filter" value="{{ $statusFilter }}">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif

                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @foreach ($statusOptions as $key => $option)
                                        <button
                                            type="button"
                                            class="membership-chip btn btn-sm rounded-pill px-3 {{ $statusFilter === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
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
                                            name="name"
                                            placeholder="Search memberships"
                                            value="{{ request('name') }}"
                                            aria-label="Search memberships"
                                        />
                                    </div>
                                </div>

                                <a
                                    href="{{ $showArchived ? route('admin.staff-account-management.memberships', ['show_archived' => 1]) : route('admin.staff-account-management.memberships') }}"
                                    class="btn btn-link text-decoration-none text-muted px-0"
                                >
                                    Reset
                                </a>

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#membershipFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="membershipFiltersModal" tabindex="-1" aria-labelledby="membershipFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="membershipFiltersModalLabel">Advanced filters</h5>
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
                                                    <label for="search-column" class="form-label text-muted text-uppercase small mb-1">Search by</label>
                                                    <select id="search-column" name="search_column" class="form-select rounded-3">
                                                        <option value="" disabled {{ request('search_column') ? '' : 'selected' }}>Select Option</option>
                                                        <option value="id" {{ request('search_column') == 'id' ? 'selected' : '' }}>ID</option>
                                                        <option value="name" {{ request('search_column') == 'name' ? 'selected' : '' }}>Name</option>
                                                        <option value="description" {{ request('search_column') == 'description' ? 'selected' : '' }}>Description</option>
                                                        <option value="month" {{ request('search_column') == 'month' ? 'selected' : '' }}>Month</option>
                                                        <option value="class_limit_per_month" {{ request('search_column') == 'class_limit_per_month' ? 'selected' : '' }}>Classes / Month</option>
                                                        <option value="members_approved" {{ request('search_column') == 'members_approved' ? 'selected' : '' }}>Total Members Approved</option>
                                                        <option value="members_pending" {{ request('search_column') == 'members_pending' ? 'selected' : '' }}>Total Members Pending</option>
                                                        <option value="members_reject" {{ request('search_column') == 'members_reject' ? 'selected' : '' }}>Total Members Reject</option>
                                                        <option value="created_at" {{ request('search_column') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                                        <option value="updated_at" {{ request('search_column') == 'updated_at' ? 'selected' : '' }}>Updated Date</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <span class="form-label text-muted text-uppercase small d-block mb-2">Date range</span>
                                                    <div class="row g-2">
                                                        <div class="col-12 col-sm-6">
                                                            <label for="start-date" class="form-label small text-muted mb-1">Start date</label>
                                                            <input
                                                                type="date"
                                                                id="start-date"
                                                                class="form-control rounded-3"
                                                                name="start_date"
                                                                value="{{ request('start_date') }}"
                                                            />
                                                        </div>
                                                        <div class="col-12 col-sm-6">
                                                            <label for="end-date" class="form-label small text-muted mb-1">End date</label>
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
                @php
                    $actionFeedbackMessage = session('success') ?? session('error');
                    $actionFeedbackIsError = session()->has('error');
                @endphp
                @if ($actionFeedbackMessage)
                    <div class="modal fade" id="actionFeedbackModal" tabindex="-1" aria-labelledby="actionFeedbackModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-body p-4 text-center">
                                    <div class="display-5 mb-3 {{ $actionFeedbackIsError ? 'text-danger' : 'text-success' }}">
                                        <i class="fa-solid {{ $actionFeedbackIsError ? 'fa-circle-exclamation' : 'fa-circle-check' }}"></i>
                                    </div>
                                    <h5 class="fw-semibold mb-2" id="actionFeedbackModalLabel">
                                        {{ $actionFeedbackIsError ? 'Something went wrong' : 'Action completed' }}
                                    </h5>
                                    <p class="text-muted mb-0">{{ $actionFeedbackMessage }}</p>
                                </div>
                                <div class="modal-footer border-0 justify-content-center pb-4 pt-0">
                                    <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">Got it</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if (!$showArchived)
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover" id="membership-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-column="id">ID <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="name">Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="description">Description <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="price">Price <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="month">Month <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="class_limit_per_month">Classes / Month <i class="fa fa-sort"></i></th>
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
                                                <td>{{ $item->description }}</td>
                                                <td>{{ $item->price }}</td>
                                                <td>{{ $item->month ?? '0' }}</td>
                                                <td>{{ $item->class_limit_per_month !== null ? $item->class_limit_per_month : 'Unlimited' }}</td>
                                                <td>{{ $item->members_approved }}</td>
                                                <td>{{ $item->members_pending }}</td>
                                                <td>{{ $item->members_reject }}</td>
                                                <td>{{ optional($item->created_at)->format('F j, Y g:iA') }}</td>
                                                <td>{{ optional($item->updated_at)->format('F j, Y g:iA') }}</td>
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
                @endif

                @if ($showArchived)
                <div class="box mt-5">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                <h4 class="fw-semibold mb-0">Archived Memberships</h4>
                                <span class="text-muted small">Showing {{ $archivedData->total() }} archived</span>
                            </div>
                            <div class="table-responsive mb-3">
                                <table class="table table-hover" id="archived-membership-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Price</th>
                                            <th>Month</th>
                                            <th>Classes / Month</th>
                                            <th>Members Approved</th>
                                            <th>Members Pending</th>
                                            <th>Members Rejected</th>
                                            <th>Created Date</th>
                                            <th>Updated Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($archivedData as $archive)
                                            <tr>
                                                <td>{{ $archive->id }}</td>
                                                <td>{{ $archive->name }}</td>
                                                <td>{{ $archive->description }}</td>
                                                <td>{{ $archive->price }}</td>
                                                <td>{{ $archive->month ?? '0' }}</td>
                                                <td>{{ $archive->class_limit_per_month !== null ? $archive->class_limit_per_month : 'Unlimited' }}</td>
                                                <td>{{ $archive->members_approved }}</td>
                                                <td>{{ $archive->members_pending }}</td>
                                                <td>{{ $archive->members_reject }}</td>
                                                <td>{{ optional($archive->created_at)->format('F j, Y g:iA') }}</td>
                                                <td>{{ optional($archive->updated_at)->format('F j, Y g:iA') }}</td>
                                                <td class="action-button">
                                                    <div class="d-flex gap-2">
                                                        <button
                                                            type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#archiveRestoreModal-{{ $archive->id }}"
                                                            data-id="{{ $archive->id }}"
                                                            title="Restore"
                                                            style="background: none; border: none; padding: 0; cursor: pointer;"
                                                        >
                                                            <i class="fa-solid fa-rotate-left text-success"></i>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#archiveDeleteModal-{{ $archive->id }}"
                                                            data-id="{{ $archive->id }}"
                                                            title="Delete"
                                                            style="background: none; border: none; padding: 0; cursor: pointer;"
                                                        >
                                                            <i class="fa-solid fa-trash text-danger"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <div class="modal fade" id="archiveRestoreModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="archiveRestoreModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="archiveRestoreModalLabel-{{ $archive->id }}">Restore membership ({{ $archive->name }})?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.staff-account-management.memberships.restore') }}" method="POST" id="archive-restore-modal-form-{{ $archive->id }}">
                                                            @csrf
                                                            <input type="hidden" name="id" value="{{ $archive->id }}">
                                                            <div class="modal-body">
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button class="btn btn-success" type="submit" id="archive-restore-modal-submit-button-{{ $archive->id }}">
                                                                    <span id="archive-restore-modal-loader-{{ $archive->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Restore
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="archiveDeleteModal-{{ $archive->id }}" tabindex="-1" aria-labelledby="archiveDeleteModalLabel-{{ $archive->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="archiveDeleteModalLabel-{{ $archive->id }}">Delete archived membership ({{ $archive->name }}) permanently?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.staff-account-management.memberships.delete') }}" method="POST" id="archive-delete-modal-form-{{ $archive->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="id" value="{{ $archive->id }}">
                                                            <div class="modal-body">
                                                                <div class="input-group mt-3">
                                                                    <input class="form-control password-input" type="password" name="password" placeholder="Enter your password">
                                                                    <button class="btn btn-outline-secondary reveal-button" type="button">Show</button>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button class="btn btn-danger" type="submit" id="archive-delete-modal-submit-button-{{ $archive->id }}">
                                                                    <span id="archive-delete-modal-loader-{{ $archive->id }}" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                                                    Submit
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>
                                                document.getElementById('archive-restore-modal-form-{{ $archive->id }}')?.addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('archive-restore-modal-submit-button-{{ $archive->id }}');
                                                    const loader = document.getElementById('archive-restore-modal-loader-{{ $archive->id }}');

                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                            <script>
                                                document.getElementById('archive-delete-modal-form-{{ $archive->id }}')?.addEventListener('submit', function(e) {
                                                    const submitButton = document.getElementById('archive-delete-modal-submit-button-{{ $archive->id }}');
                                                    const loader = document.getElementById('archive-delete-modal-loader-{{ $archive->id }}');

                                                    submitButton.disabled = true;
                                                    loader.classList.remove('d-none');
                                                });
                                            </script>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center text-muted">No archived memberships found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                {{ $archivedData->links() }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const printButton = document.getElementById('print-submit-button');
                    const printForm = document.getElementById('print-form');
                    const printLoader = document.getElementById('print-loader');

                    function text(cell) {
                        return (cell ? cell.textContent : '').replace(/\s+/g, ' ').trim();
                    }

                    function collectTableItems() {
                        const activeTable = document.getElementById('membership-table');
                        const archivedTable = document.getElementById('archived-membership-table');
                        const table = activeTable || archivedTable;
                        if (!table) return [];

                        const rows = table.querySelectorAll('tbody tr');
                        const items = [];

                        rows.forEach((row) => {
                            const cells = row.querySelectorAll('td');
                            if (!cells.length || cells[0].hasAttribute('colspan')) return;

                            const isArchived = !!archivedTable && table === archivedTable;
                            // Active table has 11 data columns before actions; archived has 11 including actions.
                            const id = text(cells[0]);
                            const name = text(cells[1]);
                            const description = text(cells[2]);
                            const price = text(cells[3]);
                            const month = text(cells[4]);
                            const classLimit = text(cells[5]);
                            const approved = text(cells[6]);
                            const pending = text(cells[7]);
                            const rejected = text(cells[8]);
                            const created = text(cells[9]);
                            const updated = text(cells[10]);

                            items.push({
                                id,
                                name,
                                description,
                                price,
                                month,
                                class_limit: classLimit,
                                approved,
                                pending,
                                rejected,
                                created,
                                updated,
                                archived: isArchived ? 'Archived' : 'Active',
                            });
                        });

                        return items;
                    }

                    function buildFilters(filters) {
                        const chips = [];
                        if (filters.show_archived) chips.push({ value: 'Archived view' });
                        if (filters.membership_status && filters.membership_status !== 'all') chips.push({ label: 'Status', value: filters.membership_status });
                        if (filters.search) {
                            chips.push({
                                label: 'Search',
                                value: `${filters.search}${filters.search_column ? ` (${filters.search_column})` : ''}`,
                            });
                        }
                        if (filters.start || filters.end) {
                            chips.push({ label: 'Date', value: `${filters.start || '—'} → ${filters.end || '—'}` });
                        }
                        return chips;
                    }

                    function buildRows(items) {
                        return items.map((item) => ([
                            item.id || '—',
                            `<div class="fw">${item.name || '—'}</div><div class="muted">${item.description || ''}</div>`,
                            `<div>₱${item.price || '0'}</div><div class="muted">Plan length: ${item.month || '0'} mo.</div><div class="muted">Classes/mo: ${item.class_limit || 'Unlimited'}</div>`,
                            `<div class="fw">Approved: ${item.approved || '0'}</div><div class="muted">Pending: ${item.pending || '0'}</div><div class="muted">Rejected: ${item.rejected || '0'}</div>`,
                            `<div>${item.created || ''}</div><div class="muted">${item.updated || ''}</div><div class="muted">${item.archived}</div>`,
                        ]));
                    }

                    function renderPrintWindow(payload) {
                        const items = payload && Array.isArray(payload.items) && payload.items.length
                            ? payload.items
                            : collectTableItems();
                        const filters = buildFilters(payload.filters || {});
                        const headers = ['ID', 'Membership', 'Plan', 'Members', 'Audit'];
                        const rows = buildRows(items);

                        return window.PrintPreview
                            ? PrintPreview.tryOpen(payload, headers, rows, filters)
                            : false;
                    }

                    if (printButton && printForm) {
                        printButton.addEventListener('click', async function (e) {
                            const rawPayload = printButton.dataset.print;
                            const rawAllPayload = printButton.dataset.printAll;
                            if (!rawPayload) return;

                            e.preventDefault();
                            if (printLoader) printLoader.classList.remove('d-none');
                            printButton.disabled = true;

                            let payload = null;
                            let allPayload = null;
                            try {
                                payload = JSON.parse(rawPayload);
                            } catch (err) {
                                payload = null;
                            }
                            try {
                                allPayload = rawAllPayload ? JSON.parse(rawAllPayload) : null;
                            } catch (err) {
                                allPayload = null;
                            }

                            const scope = window.PrintPreview && PrintPreview.chooseScope
                                ? await PrintPreview.chooseScope()
                                : 'current';

                            if (!scope) {
                                printButton.disabled = false;
                                if (printLoader) printLoader.classList.add('d-none');
                                return;
                            }

                            const payloadToUse = scope === 'all' && allPayload ? allPayload : payload;
                            const handled = payloadToUse ? renderPrintWindow(payloadToUse) : false;

                            if (!handled) {
                                printForm.submit();
                            }

                            printButton.disabled = false;
                            if (printLoader) printLoader.classList.add('d-none');
                        });
                    }
                });
            </script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('membership-filter-form');
        if (!form) {
            return;
        }
        const feedbackModalEl = document.getElementById('actionFeedbackModal');
        if (feedbackModalEl && typeof bootstrap !== 'undefined') {
            const feedbackModal = new bootstrap.Modal(feedbackModalEl);
            feedbackModal.show();
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
