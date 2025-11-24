@extends('layouts.admin')
@section('title', 'Walk-in Registration')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Walk-in Registration</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.gym-management.members.store') }}" method="POST" enctype="multipart/form-data" id="main-form">
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
                                <div class="mb-3 row">
                                    <label for="profile_picture" class="col-sm-12 col-lg-2 col-form-label">Profile Picture: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="file" class="form-control" id="profile_picture" name="profile_picture"/>
                                    </div>
                                </div>

                                <div class="alert alert-info d-flex align-items-center" role="alert">
                                    <i class="fa-solid fa-person-walking me-2"></i>
                                    This form is for in-gym walk-ins. Assign an upcoming class and collect payment to generate a receipt.
                                </div>
                                <div class="mb-3 row">
                                    <label for="first_name" class="col-sm-12 col-lg-2 col-form-label">First name: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="first_name" name="first_name" required/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="last_name" class="col-sm-12 col-lg-2 col-form-label">Last name: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="last_name" name="last_name" required/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="address" class="col-sm-12 col-lg-2 col-form-label">Address: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="address" name="address" required/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="phone_number" class="col-sm-12 col-lg-2 col-form-label">
                                        Phone number: <span class="required">*</span>
                                    </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="phone_number" 
                                            name="phone_number" 
                                            pattern="^\+639\d{9}$" 
                                            placeholder="+639XXXXXXXXX"
                                            required
                                        />
                                        <div class="invalid-feedback">
                                            Please enter a valid Philippine mobile number (e.g., +639123456789).
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label for="class_id" class="col-sm-12 col-lg-2 col-form-label">Assign Class (upcoming):</label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <select class="form-control" id="class_id" name="class_id">
                                            <option value="">-- Optional: Select a class --</option>
                                            @php
                                                $now = \Carbon\Carbon::now();
                                            @endphp
                                            @foreach(($classes ?? []) as $cls)
                                                @php
                                                    $start = $cls->class_start_date ? \Carbon\Carbon::parse($cls->class_start_date) : null;
                                                    $end = $cls->class_end_date ? \Carbon\Carbon::parse($cls->class_end_date) : null;
                                                    $isUpcoming = $start && $now->lt($start);
                                                @endphp
                                                @if($isUpcoming)
                                                    <option value="{{ $cls->id }}">
                                                        {{ $cls->name }} ({{ $start ? $start->format('M j, Y g:iA') : 'TBA' }}) — Code: {{ $cls->class_code }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="email" class="col-sm-12 col-lg-2 col-form-label">Email: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="email" class="form-control" id="email" name="email" required/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="password" class="col-sm-12 col-lg-2 col-form-label">Password: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="password" class="form-control" id="password" name="password" required/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="password_confirmation" class="col-sm-12 col-lg-2 col-form-label">Password Confirmation: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="membership_id" class="col-sm-12 col-lg-2 col-form-label">Memberships: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <select class="form-control" id="membership_id" name="membership_id" required>
                                            @foreach($memberships as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
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
    
    <script>
        document.getElementById('main-form').addEventListener('submit', function(e) {
            const submitButton = document.getElementById('submitButton');
            const loader = document.getElementById('loader');

            // Disable the button and show loader
            submitButton.disabled = true;
            loader.classList.remove('d-none');
        });
    </script>
@endsection
