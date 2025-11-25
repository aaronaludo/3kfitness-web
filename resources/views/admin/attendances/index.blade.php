@extends('layouts.admin')

@section('styles')
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Attendances')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $showArchived = request()->boolean('show_archived');
            @endphp
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Attendances</h2></div>
                <div class="d-flex align-items-center">
                    <a class="btn btn-danger" href="#attendance-scanner-card"><i class="fa-solid fa-qrcode"></i>&nbsp;&nbsp;&nbsp;Scanner</a>
                    <form action="{{ route('admin.staff-account-management.attendances.print') }}" method="POST" id="print-form">
                        @csrf
                        <input type="hidden" name="created_start" value="{{ request('start_date') }}">
                        <input type="hidden" name="created_end" value="{{ request('end_date') }}">
                        <input type="hidden" name="name" value="{{ request('name') }}">
                        <input type="hidden" name="search_column" value="{{ request('search_column') }}">
                        <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                        <button class="btn btn-danger ms-2" type="submit" id="print-submit-button">
                            <i class="fa-solid fa-print"></i>&nbsp;&nbsp;&nbsp;
                            <span id="print-loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                    @if ($showArchived)
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.staff-account-management.attendances', request()->except(['show_archived', 'page', 'archive_page'])) }}"
                        >
                            <i class="fa-solid fa-rotate-left"></i>&nbsp;&nbsp;Back to active
                        </a>
                    @else
                        <a
                            class="btn btn-outline-secondary ms-2"
                            href="{{ route('admin.staff-account-management.attendances', array_merge(request()->except(['page', 'archive_page']), ['show_archived' => 1])) }}"
                        >
                            <i class="fa-solid fa-box-archive"></i>&nbsp;&nbsp;View archived
                        </a>
                    @endif
                </div>
            </div>

            <div class="col-12 mt-4" id="attendance-scanner-card">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">QR Scanner</span>
                                <h4 class="fw-semibold mb-1">Scan attendance</h4>
                                <p class="text-muted mb-0">Enable the camera to scan staff QR codes without leaving this page.</p>
                            </div>
                            <div class="text-end">
                                <div class="btn-group" role="group" aria-label="Camera controls">
                                    <button class="btn btn-dark" type="button" id="enable-camera-btn" disabled>
                                        <i class="fa-solid fa-play"></i>&nbsp;&nbsp;Enable camera
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" id="disable-camera-btn" disabled>
                                        <i class="fa-solid fa-stop"></i>&nbsp;&nbsp;Disable camera
                                    </button>
                                </div>
                                <small class="d-block mt-2 text-muted" id="camera-status-text">Camera not ready</small>
                            </div>
                        </div>
                        <div id="attendance-scanner-wrapper" class="ratio ratio-16x9 border rounded-4 overflow-hidden bg-black mt-3 d-none">
                            <video id="attendance-scanner" class="w-100 h-100" playsinline></video>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $statusFilter = request('status', 'all');
                $statusTallies = $statusTallies ?? [];
                $statusOptions = [
                    'all' => [
                        'label' => 'All records',
                        'count' => $statusTallies['all'] ?? null,
                    ],
                    'open' => [
                        'label' => 'Pending clock-out',
                        'count' => $statusTallies['open'] ?? null,
                    ],
                    'completed' => [
                        'label' => 'Completed',
                        'count' => $statusTallies['completed'] ?? null,
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
                                <h4 class="fw-semibold mb-1">Attendance log</h4>
                                <p class="text-muted mb-0">Highlight open sessions or drill into specific days with the filters below.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">
                                    @if ($showArchived)
                                        Showing {{ $archivedData->total() }} archived attendances
                                    @else
                                        Showing {{ $data->total() }} results
                                    @endif
                                </span>
                            </div>
                        </div>

                        <form action="{{ route('admin.staff-account-management.attendances') }}" method="GET" id="attendance-filter-form" class="mt-4">
                            <input type="hidden" name="status" id="attendance-status-filter" value="{{ $statusFilter }}">
                            @if ($showArchived)
                                <input type="hidden" name="show_archived" value="1">
                            @endif

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
                                                placeholder="Search by member or role"
                                                value="{{ request('name') }}"
                                                aria-label="Search attendances"
                                            />
                                        </div>
                                    </div>

                                    <a
                                        href="{{ $showArchived ? route('admin.staff-account-management.attendances', ['show_archived' => 1]) : route('admin.staff-account-management.attendances') }}"
                                        class="btn btn-link text-decoration-none text-muted px-0"
                                    >
                                        Reset
                                    </a>

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#attendanceFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="attendanceFiltersModal" tabindex="-1" aria-labelledby="attendanceFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="attendanceFiltersModalLabel">Advanced filters</h5>
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
                                                        <option value="role" {{ request('search_column') == 'role' ? 'selected' : '' }}>Role</option>
                                                        <option value="name" {{ request('search_column') == 'name' ? 'selected' : '' }}>Name</option>
                                                        <option value="clockin_at" {{ request('search_column') == 'clockin_at' ? 'selected' : '' }}>Clock In Date</option>
                                                        <option value="clockout_at" {{ request('search_column') == 'clockout_at' ? 'selected' : '' }}>Clock Out Date</option>
                                                        <option value="created_at" {{ request('search_column') == 'created_at' ? 'selected' : '' }}>Created Date</option>
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
                @if (!$showArchived)
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                   <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-column="id">ID <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="role">Role <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="member_name">Member Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="clock_in_date">Clock In Date <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="clock_out_date">Clock Out Date <i class="fa fa-sort"></i></th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($data as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ $item->user->role->name }}</td>
                                                <td>{{ $item->user->first_name }} {{ $item->user->last_name }}</td>
                                                <td>{{ $item->clockin_at }}</td>
                                                <td>{{ $item->clockout_at }}</td>
                                                <td>
                                                    <div class="d-flex">
                                                        <div class="action-button"><a href="#" title="View"><i class="fa-solid fa-eye"></i></a></div>
                                                        <div class="action-button">
                                                            <button
                                                                type="button"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteModal-{{ $item->id }}"
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
                                            <div class="modal fade" id="deleteModal-{{ $item->id }}" tabindex="-1" aria-labelledby="deleteModalLabel-{{ $item->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel-{{ $item->id }}">Are you sure you want to delete attendance #{{ $item->id }}?</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('admin.staff-account-management.attendances.delete') }}" method="POST" id="main-form-{{ $item->id }}">
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
                                                document.getElementById('main-form-{{ $item->id }}')?.addEventListener('submit', function (e) {
                                                    const submitButton = document.getElementById('submitButton-{{ $item->id }}');
                                                    const loader = document.getElementById('loader-{{ $item->id }}');

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
            </div>
        </div>
    </div>

    @if ($showArchived)
    <div class="box mt-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <h4 class="fw-semibold mb-0">Archived Attendance</h4>
                    <span class="text-muted small">Showing {{ $archivedData->total() }} archived</span>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Role</th>
                                <th>Member Name</th>
                                <th>Clock In Date</th>
                                <th>Clock Out Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($archivedData as $archive)
                                <tr>
                                    <td>{{ $archive->id }}</td>
                                    <td>{{ optional(optional($archive->user)->role)->name }}</td>
                                    <td>{{ optional($archive->user)->first_name }} {{ optional($archive->user)->last_name }}</td>
                                    <td>{{ $archive->clockin_at }}</td>
                                    <td>{{ $archive->clockout_at }}</td>
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
                                                <h5 class="modal-title" id="archiveRestoreModalLabel-{{ $archive->id }}">Restore attendance #{{ $archive->id }}?</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('admin.staff-account-management.attendances.restore') }}" method="POST" id="archive-restore-modal-form-{{ $archive->id }}">
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
                                                <h5 class="modal-title" id="archiveDeleteModalLabel-{{ $archive->id }}">Delete attendance #{{ $archive->id }} permanently?</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('admin.staff-account-management.attendances.delete') }}" method="POST" id="archive-delete-modal-form-{{ $archive->id }}">
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
                                    document.getElementById('archive-restore-modal-form-{{ $archive->id }}')?.addEventListener('submit', function (e) {
                                        const submitButton = document.getElementById('archive-restore-modal-submit-button-{{ $archive->id }}');
                                        const loader = document.getElementById('archive-restore-modal-loader-{{ $archive->id }}');

                                        submitButton.disabled = true;
                                        loader.classList.remove('d-none');
                                    });
                                </script>
                                <script>
                                    document.getElementById('archive-delete-modal-form-{{ $archive->id }}')?.addEventListener('submit', function (e) {
                                        const submitButton = document.getElementById('archive-delete-modal-submit-button-{{ $archive->id }}');
                                        const loader = document.getElementById('archive-delete-modal-loader-{{ $archive->id }}');

                                        submitButton.disabled = true;
                                        loader.classList.remove('d-none');
                                    });
                                </script>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No archived attendance records found.</td>
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

    <div class="modal fade" id="scannerModal" tabindex="-1" aria-labelledby="scannerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scannerModalLabel">Scanned Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="modalContent" class="mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const scannerWrapper = document.getElementById('attendance-scanner-wrapper');
            const scannerVideo = document.getElementById('attendance-scanner');
            const enableCameraBtn = document.getElementById('enable-camera-btn');
            const disableCameraBtn = document.getElementById('disable-camera-btn');
            const cameraStatusText = document.getElementById('camera-status-text');
            const modalContent = document.getElementById('modalContent');
            const scannerModalElement = document.getElementById('scannerModal');

            let scannerInstance = null;
            let availableCameras = [];
            let activeCameraIndex = 0;
            let isCameraRunning = false;

            function toggleScannerVisibility(show) {
                if (!scannerWrapper) {
                    return;
                }

                scannerWrapper.classList.toggle('d-none', !show);
            }

            function setCameraStatus(message, variant = 'muted') {
                if (!cameraStatusText) {
                    return;
                }

                cameraStatusText.textContent = message;
                cameraStatusText.classList.remove('text-muted', 'text-success', 'text-danger');

                const variantClassMap = {
                    muted: 'text-muted',
                    success: 'text-success',
                    danger: 'text-danger',
                };

                cameraStatusText.classList.add(variantClassMap[variant] ?? 'text-muted');
            }

            function syncCameraButtons() {
                if (enableCameraBtn) {
                    enableCameraBtn.disabled = isCameraRunning || !availableCameras.length;
                }
                if (disableCameraBtn) {
                    disableCameraBtn.disabled = !isCameraRunning;
                }
            }

            function startCamera() {
                if (!scannerInstance || !availableCameras.length) {
                    setCameraStatus('No camera found', 'danger');
                    return;
                }

                scannerInstance.start(availableCameras[activeCameraIndex]).then(function () {
                    isCameraRunning = true;
                    syncCameraButtons();
                    toggleScannerVisibility(true);
                    setCameraStatus('Camera active', 'success');
                }).catch(function (error) {
                    console.error(error);
                    setCameraStatus('Unable to start camera', 'danger');
                    toggleScannerVisibility(false);
                });
            }

            function stopCamera() {
                if (!scannerInstance || !isCameraRunning) {
                    return;
                }

                Promise.resolve(scannerInstance.stop()).then(function () {
                    isCameraRunning = false;
                    syncCameraButtons();
                    setCameraStatus('Camera disabled');
                    toggleScannerVisibility(false);
                }).catch(function (error) {
                    console.error(error);
                    setCameraStatus('Error while stopping camera', 'danger');
                    toggleScannerVisibility(false);
                });
            }

            function sendScannedData(content) {
                const csrfMeta = document.querySelector("meta[name='csrf-token']");
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;

                if (!csrfToken) {
                    console.warn('CSRF token missing; skipping attendance lookup');
                    return;
                }

                fetch("{{ route('admin.staff-account-management.attendances.scanner2.fetch') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ result: content })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (modalContent) {
                            modalContent.textContent = data.data ?? 'No data returned';
                        }
                        if (scannerModalElement) {
                            const scannerModal = new bootstrap.Modal(scannerModalElement);
                            scannerModal.show();
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        setCameraStatus('Scan failed — see console', 'danger');
                    });
            }

            toggleScannerVisibility(false);
            syncCameraButtons();

            if (scannerVideo && typeof Instascan !== 'undefined') {
                scannerInstance = new Instascan.Scanner({ video: scannerVideo, mirror: false });

                scannerInstance.addListener('scan', function (content) {
                    sendScannedData(content);
                });

                Instascan.Camera.getCameras().then(function (cameras) {
                    availableCameras = cameras;
                    if (!cameras.length) {
                        toggleScannerVisibility(false);
                        setCameraStatus('No camera found', 'danger');
                    } else {
                        setCameraStatus('Camera ready — enable to start');
                    }
                    syncCameraButtons();
                }).catch(function (error) {
                    console.error(error);
                    toggleScannerVisibility(false);
                    setCameraStatus('Camera access denied', 'danger');
                    syncCameraButtons();
                });
            } else if (scannerVideo) {
                setCameraStatus('Scanner script unavailable', 'danger');
                syncCameraButtons();
                toggleScannerVisibility(false);
            }

            if (enableCameraBtn) {
                enableCameraBtn.addEventListener('click', function () {
                    if (isCameraRunning) {
                        return;
                    }

                    if (typeof Instascan === 'undefined') {
                        setCameraStatus('Scanner script unavailable', 'danger');
                        return;
                    }

                    if (!availableCameras.length) {
                        Instascan.Camera.getCameras().then(function (cameras) {
                            availableCameras = cameras;
                            syncCameraButtons();

                            if (!cameras.length) {
                                toggleScannerVisibility(false);
                                setCameraStatus('No camera found', 'danger');
                                return;
                            }

                            startCamera();
                        }).catch(function (error) {
                            console.error(error);
                            toggleScannerVisibility(false);
                            setCameraStatus('Camera access denied', 'danger');
                        });

                        return;
                    }

                    startCamera();
                });
            }

            if (disableCameraBtn) {
                disableCameraBtn.addEventListener('click', function () {
                    stopCamera();
                });
            }

            window.addEventListener('beforeunload', function () {
                if (scannerInstance && isCameraRunning) {
                    scannerInstance.stop();
                }
                toggleScannerVisibility(false);
            });

            const form = document.getElementById('attendance-filter-form');
            if (!form) {
                return;
            }

            const statusInput = document.getElementById('attendance-status-filter');
            const chipButtons = form.querySelectorAll('.status-chip');
            const rangeButtons = form.querySelectorAll('.range-chip');
            const startInput = document.getElementById('start-date');
            const endInput = document.getElementById('end-date');
            const printForm = document.getElementById('print-form');
            const printButton = document.getElementById('print-submit-button');
            const printLoader = document.getElementById('print-loader');

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
                    if (statusInput) {
                        statusInput.value = selectedStatus;
                    }

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

            if (printForm && printButton && printLoader) {
                printForm.addEventListener('submit', function () {
                    printButton.disabled = true;
                    printLoader.classList.remove('d-none');
                });
            }
        });
    </script>
@endsection
