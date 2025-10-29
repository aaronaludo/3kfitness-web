@extends('layouts.admin')
@section('title', 'Abouts')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Abouts</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.abouts.update') }}" method="POST" enctype="multipart/form-data" id="main-form">
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
                                <input type="hidden" name="id" value="{{ $data->id ?? 0 }}" />
                                <div class="mb-3 row">
                                    <label for="terms_and_conditions" class="col-sm-12 col-lg-2 col-form-label">Terms and Conditions: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <textarea class="form-control" id="terms_and_conditions" name="terms_and_conditions" rows="4" required>{{ $data->terms_and_conditions ?? "" }}</textarea>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="data_policy" class="col-sm-12 col-lg-2 col-form-label">Data Policy: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <textarea class="form-control" id="data_policy" name="data_policy" rows="4" required>{{ $data->data_policy ?? "" }}</textarea>
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
