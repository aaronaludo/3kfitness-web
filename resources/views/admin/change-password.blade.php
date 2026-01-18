@extends('layouts.admin')
@section('title', 'Change Password')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Change Password</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.account.update_change_password') }}" method="POST" id="change-password-form">
                                @csrf
                                <div class="mb-3 row">
                                    <label for="old_password" class="col-sm-12 col-lg-2 col-form-label">Old Password: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="password" class="form-control" id="old_password" name="old_password" required/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="new_password" class="col-sm-12 col-lg-2 col-form-label">New Password: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12">
                                        <div class="d-flex align-items-center">
                                            <input type="password" class="form-control" id="new_password" name="new_password" required/>
                                        </div>
                                        <div class="form-text d-flex align-items-center gap-2 d-none" id="password-strength" aria-live="polite">
                                            <span class="badge bg-secondary" id="password-strength-badge">Strength</span>
                                            <span id="password-strength-text">Use 8+ characters with upper, lower, number, symbol.</span>
                                        </div>
                                        <div class="text-danger small mt-1 d-none" id="password-strength-error" role="alert"></div>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="new_password_confirmation" class="col-sm-12 col-lg-2 col-form-label">Confirm New Password: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required/>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-center mt-5 mb-4">
                                    <button class="btn btn-danger" type="submit">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('change-password-form');
            var newPasswordInput = document.getElementById('new_password');
            var strengthRow = document.getElementById('password-strength');
            var strengthBadge = document.getElementById('password-strength-badge');
            var strengthText = document.getElementById('password-strength-text');
            var strengthError = document.getElementById('password-strength-error');

            if (!form || !newPasswordInput || !strengthRow || !strengthBadge || !strengthText || !strengthError) {
                return;
            }

            var strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
            var weakPasswordRegex = /^(?=.*[A-Za-z])(?=.*\d).{6,}$/;

            function setBadge(label, classes) {
                strengthBadge.textContent = label;
                strengthBadge.className = 'badge ' + classes;
            }

            function updateStrength() {
                var value = newPasswordInput.value || '';

                if (!value) {
                    strengthRow.classList.add('d-none');
                    strengthError.classList.add('d-none');
                    strengthError.textContent = '';
                    return;
                }

                strengthRow.classList.remove('d-none');

                if (strongPasswordRegex.test(value)) {
                    setBadge('Strong', 'bg-success');
                    strengthText.textContent = 'Great! Your password is strong.';
                    strengthError.classList.add('d-none');
                    strengthError.textContent = '';
                    return;
                }

                if (weakPasswordRegex.test(value)) {
                    setBadge('Weak', 'bg-warning text-dark');
                    strengthText.textContent = 'Use 8+ characters with upper, lower, number, symbol.';
                    return;
                }

                setBadge('Very weak', 'bg-danger');
                strengthText.textContent = 'Add letters, numbers, and symbols to strengthen it.';
            }

            newPasswordInput.addEventListener('input', updateStrength);

            form.addEventListener('submit', function (event) {
                var value = newPasswordInput.value || '';

                if (!value) {
                    return;
                }

                if (!strongPasswordRegex.test(value)) {
                    event.preventDefault();
                    var weakMatch = weakPasswordRegex.test(value);
                    strengthError.textContent = weakMatch
                        ? 'Password is weak. Use 8+ characters with uppercase, lowercase, number, and symbol.'
                        : 'Password is too weak. Add letters, numbers, and symbols.';
                    strengthError.classList.remove('d-none');
                    newPasswordInput.focus();
                } else {
                    strengthError.classList.add('d-none');
                    strengthError.textContent = '';
                }
            });

            updateStrength();
        });
    </script>
@endsection
