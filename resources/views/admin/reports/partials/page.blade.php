@section('styles')
<style>
    .report-hero {
        background: linear-gradient(135deg, #0f4c75 0%, #1b6ca8 45%, #3282b8 100%);
        color: #f5fbff;
        border-radius: 20px;
        padding: 24px 26px;
        box-shadow: 0 18px 45px rgba(15, 76, 117, 0.25);
        position: relative;
        overflow: hidden;
    }
    .report-hero::after {
        content: "";
        position: absolute;
        inset: 10% -10% -40% 50%;
        background: radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 55%);
        transform: rotate(-8deg);
    }
    .report-hero h2 {
        font-weight: 800;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    .report-hero .eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-weight: 700;
        font-size: 0.8rem;
        opacity: 0.85;
    }
    .pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.15);
        color: #f5fbff;
        font-weight: 700;
        border: 1px solid rgba(255, 255, 255, 0.25);
    }
    .pill i { font-size: 0.9rem; }
    .summary-card {
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,0.05);
        background: #fff;
        padding: 18px;
        box-shadow: 0 14px 38px rgba(0, 0, 0, 0.05);
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .summary-card .eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-weight: 700;
        font-size: 0.82rem;
        color: #5b6470;
    }
    .summary-value {
        font-size: 1.8rem;
        font-weight: 800;
        margin: 6px 0;
        color: #1f2a37;
    }
    .summary-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 10px;
        background: rgba(15, 76, 117, 0.06);
        color: #0f4c75;
        font-weight: 700;
        font-size: 0.85rem;
    }
    .filter-card {
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,0.05);
        background: #fff;
        box-shadow: 0 10px 28px rgba(0,0,0,0.06);
    }
    .form-label {
        font-weight: 700;
        color: #4b5563;
        font-size: 0.9rem;
    }
    .input-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
    }
    .search-input { padding-left: 38px; }
    .table thead th {
        border: none;
        color: #5b6470;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-size: 0.78rem;
    }
    .table tbody td { vertical-align: middle; }
    .badge-soft {
        background: rgba(12, 108, 178, 0.08);
        color: #0c6cb2;
        border-radius: 12px;
        padding: 6px 10px;
        font-weight: 700;
        font-size: 0.85rem;
    }
    .empty-state {
        padding: 24px;
        text-align: center;
        color: #6b7280;
    }
    .note-box {
        border-radius: 14px;
        border: 1px dashed rgba(12, 108, 178, 0.25);
        background: rgba(12, 108, 178, 0.05);
        padding: 12px 14px;
        color: #0f4c75;
        font-weight: 600;
    }
    @media (max-width: 575.98px) {
        .report-hero { padding: 18px; }
        .summary-value { font-size: 1.5rem; }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="report-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8 position-relative" style="z-index: 1;">
                <p class="eyebrow mb-1">Reports Center</p>
                <h2>Reports</h2>
                <p class="mb-3">Use the filters below to quickly search and review report records in the same layout as Sales Report.</p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="pill"><i class="fa-solid fa-layer-group"></i> Unified view</span>
                    <span class="pill"><i class="fa-solid fa-clock"></i> Updated daily</span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end text-start position-relative" style="z-index: 1;">
                <div class="note-box">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    Consistent styling keeps navigation and filtering predictable for users.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="summary-card h-100">
                <p class="eyebrow mb-1">Total Records</p>
                <div class="summary-value">2</div>
                <div class="summary-chip">
                    <i class="fa-solid fa-database"></i>
                    Sample data loaded
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card h-100">
                <p class="eyebrow mb-1">Active</p>
                <div class="summary-value">1</div>
                <div class="summary-chip">
                    <i class="fa-solid fa-circle-check"></i>
                    Status: Active
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card h-100">
                <p class="eyebrow mb-1">Inactive</p>
                <div class="summary-value">1</div>
                <div class="summary-chip">
                    <i class="fa-solid fa-circle-xmark"></i>
                    Status: Inactive
                </div>
            </div>
        </div>
    </div>

    <div class="filter-card mb-4 p-4">
        <div class="card-body">
            <form action="{{ route('admin.reports.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-lg-5">
                    <label for="search" class="form-label">Search by name</label>
                    <div class="position-relative">
                        <i class="fa-solid fa-magnifying-glass input-icon"></i>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control search-input"
                            placeholder="Search by Name"
                            value="{{ request('search') }}"
                        />
                    </div>
                </div>
                <div class="col-12 col-lg-3">
                    <label for="type" class="form-label">Type</label>
                    <select id="type" name="type" class="form-select">
                        <option value="">All</option>
                        <option value="admin" {{ request('type') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="manager" {{ request('type') === 'manager' ? 'selected' : '' }}>Manager</option>
                    </select>
                </div>
                <div class="col-12 col-lg-3">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Any</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-12 col-lg-auto d-flex gap-2">
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-link text-decoration-none text-muted px-0">
                        Reset
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-filter me-2"></i>Apply filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden p-3">
                <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0">Results</h5>
                        <small class="text-muted">Consistent table styling with Sales Report.</small>
                    </div>
                    <div class="badge-soft"><i class="fa-solid fa-clipboard-list me-1"></i> Reports</div>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th class="text-center">Contact Number</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>John Doe</td>
                                <td><span class="badge-soft">Admin</span></td>
                                <td class="text-center">+123456789</td>
                                <td class="text-center"><span class="badge-soft">Active</span></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end">
                                        <a href="#" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye me-1"></i> View</a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Jane Smith</td>
                                <td><span class="badge-soft">Manager</span></td>
                                <td class="text-center">+987654321</td>
                                <td class="text-center"><span class="badge-soft">Inactive</span></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end">
                                        <a href="#" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye me-1"></i> View</a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
