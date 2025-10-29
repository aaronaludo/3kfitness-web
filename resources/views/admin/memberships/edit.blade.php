@extends('layouts.admin')
@section('title', 'Edit Membership')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Edit Membership</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.staff-account-management.memberships.update', $data->id) }}" method="POST" enctype="multipart/form-data" id="main-form">
                                @csrf
                                @METHOD('PUT')
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
                                        <input type="text" class="form-control" id="name" name="name" value="{{ $data->name }}" required/>
                                    </div>
                                </div>   
                                <!--<div class="mb-3 row">-->
                                <!--    <label for="currency" class="col-sm-12 col-lg-2 col-form-label">Currency: <span class="required">*</span></label>-->
                                <!--    <div class="col-lg-10 col-sm-12 d-flex align-items-center">-->
                                <!--        <input type="text" class="form-control" id="currency" name="currency" value="{{ $data->currency }}" required/>-->
                                <!--    </div>-->
                                <!--</div>                  -->
                                <div class="mb-3 row">
                                    <label for="price" class="col-sm-12 col-lg-2 col-form-label">Price: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="number" class="form-control" id="price" name="price" value="{{ $data->price }}" required/>
                                    </div>
                                </div>
                                <!--<div class="mb-3 row">-->
                                <!--    <label for="year" class="col-sm-12 col-lg-2 col-form-label">Year: </label>-->
                                <!--    <div class="col-lg-10 col-sm-12 d-flex align-items-center">-->
                                <!--        <input type="number" class="form-control" id="year" name="year" value="{{ $data->year ?? '0' }}"/>-->
                                <!--    </div>-->
                                <!--</div>       -->
                                <div class="mb-3 row">
                                    <label for="month" class="col-sm-12 col-lg-2 col-form-label">Month: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="number" class="form-control" id="month" name="month" value="{{ $data->month ?? '0' }}"/>
                                    </div>
                                </div>       
                                <!--<div class="mb-3 row">-->
                                <!--    <label for="week" class="col-sm-12 col-lg-2 col-form-label">Week: </label>-->
                                <!--    <div class="col-lg-10 col-sm-12 d-flex align-items-center">-->
                                <!--        <input type="number" class="form-control" id="week" name="week" value="{{ $data->week ?? '0' }}"/>-->
                                <!--    </div>-->
                                <!--</div>       -->
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
