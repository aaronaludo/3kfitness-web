@extends('layouts.admin')
@section('title', 'Create Membership')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Create Membership</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.staff-account-management.memberships.store') }}" method="POST" enctype="multipart/form-data" id="main-form">
                                @csrf
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                @if (session('success'))
                                    <div class="alert alert-success">
                                        {{ session('success') }}
                                    </div>
                                @endif
                                <div class="mb-3 row">
                                    <label for="name" class="col-sm-12 col-lg-2 col-form-label">Name: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="name" name="name" required/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="description" class="col-sm-12 col-lg-2 col-form-label">Description:</label>
                                    <div class="col-lg-10 col-sm-12">
                                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Provide membership details"></textarea>
                                    </div>
                                </div>   
                                <!--<div class="mb-3 row">-->
                                <!--    <label for="currency" class="col-sm-12 col-lg-2 col-form-label">Currency: <span class="required">*</span></label>-->
                                <!--    <div class="col-lg-10 col-sm-12 d-flex align-items-center">-->
                                <!--        <input type="text" class="form-control" id="currency" name="currency" required/>-->
                                <!--    </div>-->
                                <!--</div>                  -->
                                <div class="mb-3 row">
                                    <label for="price" class="col-sm-12 col-lg-2 col-form-label">Price: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="number" class="form-control" id="price" name="price" required/>
                                    </div>
                                </div>      
                                <!--<div class="mb-3 row">-->
                                <!--    <label for="year" class="col-sm-12 col-lg-2 col-form-label">Year: </label>-->
                                <!--    <div class="col-lg-10 col-sm-12 d-flex align-items-center">-->
                                <!--        <input type="number" class="form-control" id="year" name="year" value="0" required/>-->
                                <!--    </div>-->
                                <!--</div>       -->
                                <div class="mb-3 row">
                                    <label for="month" class="col-sm-12 col-lg-2 col-form-label">Month: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="number" class="form-control" id="month" name="month" value="{{ old('month', 0) }}" required/>
                                    </div>
                                </div>       
                                <div class="mb-3 row">
                                    <label for="class_limit_per_month" class="col-sm-12 col-lg-2 col-form-label">Classes / Month:</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input
                                            type="number"
                                            min="0"
                                            class="form-control"
                                            id="class_limit_per_month"
                                            name="class_limit_per_month"
                                            placeholder="Leave blank for unlimited"
                                            value="{{ old('class_limit_per_month') }}"
                                        />
                                    </div>
                                </div>
                                <!--<div class="mb-3 row">-->
                                <!--    <label for="week" class="col-sm-12 col-lg-2 col-form-label">Week: </label>-->
                                <!--    <div class="col-lg-10 col-sm-12 d-flex align-items-center">-->
                                <!--        <input type="number" class="form-control" id="week" name="week" value="0" required/>-->
                                <!--    </div>-->
                                <!--</div>        -->
                                <div class="d-flex justify-content-center mt-5 mb-4">
                                    <button class="btn btn-danger" type="submit" id="submitButton">
                                        <span id="loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                        Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="formConfirmModal" tabindex="-1" aria-labelledby="formConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="formConfirmModalLabel">Create membership?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Review the summary before creating this plan.</p>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Name</span>
                            <span class="fw-semibold" id="confirmName">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Price</span>
                            <span class="fw-semibold" id="confirmPrice">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Month(s)</span>
                            <span class="fw-semibold" id="confirmMonth">—</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Classes / Month</span>
                            <span class="fw-semibold" id="confirmClassLimit">—</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Review again</button>
                    <button type="button" class="btn btn-danger" id="confirmActionButton">
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="confirmActionLoader" role="status" aria-hidden="true"></span>
                        Yes, create it
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const form = document.getElementById('main-form');
        const submitButton = document.getElementById('submitButton');
        const loader = document.getElementById('loader');
        const nameInput = document.getElementById('name');
        const priceInput = document.getElementById('price');
        const monthInput = document.getElementById('month');
        const classLimitInput = document.getElementById('class_limit_per_month');
        const confirmName = document.getElementById('confirmName');
        const confirmPrice = document.getElementById('confirmPrice');
        const confirmMonth = document.getElementById('confirmMonth');
        const confirmClassLimit = document.getElementById('confirmClassLimit');
        const confirmModalEl = document.getElementById('formConfirmModal');
        const confirmModal = confirmModalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(confirmModalEl) : null;
        const confirmActionButton = document.getElementById('confirmActionButton');
        const confirmActionLoader = document.getElementById('confirmActionLoader');
        let allowSubmit = false;

        const formatCurrency = (value) => {
            if (!value) return '—';
            const num = Number(value);
            if (Number.isNaN(num)) return value;
            return `₱${num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        };

        const populateConfirmation = () => {
            confirmName.textContent = nameInput?.value?.trim() || '—';
            confirmPrice.textContent = formatCurrency(priceInput?.value);
            confirmMonth.textContent = monthInput?.value !== '' && monthInput?.value !== null ? monthInput.value : '0';
            const classLimit = classLimitInput?.value?.trim();
            confirmClassLimit.textContent = classLimit === '' ? 'Unlimited' : classLimit;
        };

        form?.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                return;
            }
            if (!allowSubmit) {
                e.preventDefault();
                populateConfirmation();
                if (confirmModal) {
                    confirmModal.show();
                } else {
                    allowSubmit = true;
                    submitButton.disabled = true;
                    loader.classList.remove('d-none');
                    form.submit();
                }
            } else {
                submitButton.disabled = true;
                loader.classList.remove('d-none');
            }
        });

        confirmActionButton?.addEventListener('click', function () {
            allowSubmit = true;
            submitButton.disabled = true;
            confirmActionButton.disabled = true;
            confirmActionLoader.classList.remove('d-none');
            loader.classList.remove('d-none');
            confirmModal?.hide();
            form.submit();
        });
    </script>
@endsection
