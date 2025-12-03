@extends('layouts.admin')
@section('title', 'Logs')

@section('content')
    <div class="container-fluid">
        <div class="row">
            @php
                $searchTerm = request('search');
                $roleFilter = request('search_column', '');
                $roleOptions = [
                    '' => ['label' => 'All'],
                    'Admin' => ['label' => 'Admin'],
                    'Staff' => ['label' => 'Staff'],
                    'Member' => ['label' => 'Member'],
                    'Trainer' => ['label' => 'Trainer'],
                ];
                $advancedFiltersOpen = request()->filled('sort_column');
                $printSource = $data;
                $printLogs = collect($printSource->items())->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'message' => $item->message,
                        'role_name' => $item->role_name,
                        'created_at' => optional($item->created_at)->format('M j, Y g:i A') ?? '',
                    ];
                })->values();

                $printPayload = [
                    'title' => 'Logs',
                    'generated_at' => now()->format('M d, Y g:i A'),
                    'filters' => [
                        'search' => $searchTerm,
                        'search_column' => request('search_column'),
                        'sort' => request('sort_column', 'DESC'),
                    ],
                    'count' => $printLogs->count(),
                    'items' => $printLogs,
                ];
            @endphp
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Logs</h2></div>
                <div class="d-flex align-items-center">
                    <form action="{{ route('admin.logs.print') }}" method="POST" id="print-form">
                        @csrf
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="search_column" value="{{ request('search_column') }}">
                        <input type="hidden" name="sort_column" value="{{ request('sort_column', 'DESC') }}">
                        <button
                            class="btn btn-danger ms-2"
                            type="submit"
                            id="print-submit-button"
                            data-print='@json($printPayload)'
                            aria-label="Open printable/PDF view of filtered logs"
                        >
                            <i class="fa-solid fa-print"></i>
                            <span id="print-loader" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-12 mb-20 mt-3">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                            <div>
                                <span class="badge bg-light text-dark fw-semibold px-3 py-2 rounded-pill text-uppercase small mb-2">Overview</span>
                                <h4 class="fw-semibold mb-1">Activity logs</h4>
                                <p class="text-muted mb-0">Filter log entries by role, search text, or sort order.</p>
                            </div>
                            <div class="text-end">
                                <span class="d-block text-muted small">Showing {{ $data->total() }} results</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.logs.index') }}" method="GET" id="logs-filter-form" class="mt-4">
                            <input type="hidden" name="search_column" id="logs-role-filter" value="{{ $roleFilter }}">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @foreach ($roleOptions as $key => $option)
                                        <button
                                            type="button"
                                            class="role-chip btn btn-sm rounded-pill px-3 {{ $roleFilter === $key ? 'btn-dark text-white shadow-sm' : 'btn-outline-secondary text-dark' }}"
                                            data-role="{{ $key }}"
                                        >
                                            {{ $option['label'] }}
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
                                                placeholder="Search by message"
                                                value="{{ request('search') }}"
                                                aria-label="Search logs"
                                            />
                                        </div>
                                    </div>

                                    <a
                                        href="{{ route('admin.logs.index') }}"
                                        class="btn btn-link text-decoration-none text-muted px-0"
                                    >
                                        Reset
                                    </a>

                                    <button
                                        class="btn {{ $advancedFiltersOpen ? 'btn-secondary text-white' : 'btn-outline-secondary' }} rounded-pill px-3"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#logsFiltersModal"
                                    >
                                        <i class="fa-solid fa-sliders"></i> Filters
                                    </button>

                                    <button type="submit" class="btn btn-danger rounded-pill px-4 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="logsFiltersModal" tabindex="-1" aria-labelledby="logsFiltersModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-md">
                                    <div class="modal-content rounded-4 border-0 shadow-sm">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold" id="logsFiltersModalLabel">Advanced filters</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex flex-column gap-4">
                                                <div>
                                                    <label for="logs-role-select" class="form-label text-muted text-uppercase small mb-1">Role</label>
                                                    <select id="logs-role-select" class="form-select rounded-3">
                                                        @foreach ($roleOptions as $key => $option)
                                                            <option value="{{ $key }}" {{ $roleFilter === $key ? 'selected' : '' }}>{{ $option['label'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label for="logs-sort-select" class="form-label text-muted text-uppercase small mb-1">Sort</label>
                                                    <select id="logs-sort-select" name="sort_column" class="form-select rounded-3">
                                                        <option value="DESC" {{ request('sort_column', 'DESC') === 'DESC' ? 'selected' : '' }}>Newest first</option>
                                                        <option value="ASC" {{ request('sort_column') === 'ASC' ? 'selected' : '' }}>Oldest first</option>
                                                    </select>
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
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-column="id"># <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="message">Message <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="role_name">Role Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($data as $item)
                                            <tr>
                                                <td>{{ $item->id  }}</td>
                                                <td>{{ $item->message }}</td>
                                                <td>{{ $item->role_name }}</td>
                                                <td>{{ optional($item->created_at)->format('F j, Y g:iA') }}</td>
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
            const printForm = document.getElementById('print-form');
            const printButton = document.getElementById('print-submit-button');
            const printLoader = document.getElementById('print-loader');
            const logsFilterForm = document.getElementById('logs-filter-form');
            const roleInput = document.getElementById('logs-role-filter');
            const roleChips = logsFilterForm ? logsFilterForm.querySelectorAll('.role-chip') : [];
            const roleSelect = document.getElementById('logs-role-select');

            function buildFilters(filters) {
                const chips = [];
                if (filters.search) {
                    chips.push(`Search: ${filters.search}${filters.search_column ? ` (${filters.search_column})` : ''}`);
                }
                if (filters.sort) {
                    chips.push(`Sort: ${filters.sort === 'ASC' ? 'Ascending' : 'Descending'}`);
                }
                return chips.map((chip) => `<span class="pill">${chip}</span>`).join('') || '<span class="muted">No filters applied</span>';
            }

            function buildRows(items) {
                return items.map((item) => `
                    <tr>
                        <td>${item.id ?? '—'}</td>
                        <td>${item.message || '—'}</td>
                        <td>${item.role_name || '—'}</td>
                        <td>${item.created_at || ''}</td>
                    </tr>
                `).join('');
            }

            function renderPrintWindow(payload) {
                const items = payload.items || [];
                const filters = payload.filters || {};
                const rows = buildRows(items);
                const html = `
                    <!doctype html>
                    <html>
                        <head>
                            <title>${payload.title || 'Logs'}</title>
                            <style>
                                :root { color-scheme: light; }
                                body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 24px; color: #111827; }
                                .sheet { max-width: 1000px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px 28px; }
                                .header { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
                                .title { margin: 0; font-size: 22px; }
                                .muted { color: #6b7280; font-size: 12px; }
                                .pill-row { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; }
                                .pill { background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 999px; padding: 6px 12px; font-size: 12px; }
                                table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 13px; }
                                th, td { border: 1px solid #e5e7eb; padding: 10px; vertical-align: top; }
                                th { background: #f9fafb; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
                            </style>
                        </head>
                        <body>
                            <div class="sheet">
                                <div class="header">
                                    <div>
                                        <h1 class="title">${payload.title || 'Logs'}</h1>
                                        <div class="muted">Generated ${payload.generated_at || ''}</div>
                                        <div class="muted">Showing ${payload.count || 0} record(s)</div>
                                    </div>
                                </div>
                                <div class="pill-row">${buildFilters(filters)}</div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Message</th>
                                            <th>Role</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows || '<tr><td colspan="4" style="text-align:center; padding:16px;">No logs for this view.</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                            <script>window.print();<\/script>
                        </body>
                    </html>
                `;

                const printWindow = window.open('', '_blank', 'width=1100,height=900');
                if (!printWindow) return false;
                printWindow.document.open();
                printWindow.document.write(html);
                printWindow.document.close();
                return true;
            }

            if (printButton && printForm) {
                printButton.addEventListener('click', function (e) {
                    const rawPayload = printButton.dataset.print;
                    if (!rawPayload) {
                        return;
                    }

                    e.preventDefault();
                    if (printLoader) printLoader.classList.remove('d-none');
                    printButton.disabled = true;

                    let payload = null;
                    try {
                        payload = JSON.parse(rawPayload);
                    } catch (err) {
                        payload = null;
                    }

                    const opened = payload ? renderPrintWindow(payload) : false;
                    if (!opened) {
                        printButton.disabled = false;
                        if (printLoader) printLoader.classList.add('d-none');
                        printForm.submit();
                        return;
                    }

                    setTimeout(() => {
                        printButton.disabled = false;
                        if (printLoader) printLoader.classList.add('d-none');
                    }, 300);
                });
            }

            if (logsFilterForm) {
                roleChips.forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        const selectedRole = this.dataset.role ?? '';
                        if (roleInput) {
                            roleInput.value = selectedRole;
                        }

                        roleChips.forEach(function (btn) {
                            btn.classList.remove('btn-dark', 'text-white', 'shadow-sm');
                            if (!btn.classList.contains('btn-outline-secondary')) {
                                btn.classList.add('btn-outline-secondary');
                            }
                        });

                        this.classList.remove('btn-outline-secondary');
                        this.classList.add('btn-dark', 'text-white', 'shadow-sm');

                        logsFilterForm.submit();
                    });
                });

                if (roleSelect && roleInput) {
                    roleSelect.addEventListener('change', function () {
                        roleInput.value = this.value || '';
                    });
                }
            }
        });
    </script>
@endsection
