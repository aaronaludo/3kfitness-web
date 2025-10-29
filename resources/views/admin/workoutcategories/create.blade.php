@extends('layouts.admin')
@section('title', 'Create Workout Categories')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Create Workout Categories</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.workout-categories.store') }}" method="POST" enctype="multipart/form-data" id="main-form">
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
                                    <label for="title" class="col-sm-12 col-lg-2 col-form-label">Title: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="title" name="title" required/>
                                    </div>
                                </div>   
                                <div class="mb-3 row">
                                    <label for="calories" class="col-sm-12 col-lg-2 col-form-label">Calories: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="calories" name="calories" required/>
                                    </div>
                                </div>   
                                <div class="mb-3 row">
                                    <label for="equipment" class="col-sm-12 col-lg-2 col-form-label">Equipment: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="equipment" name="equipment" required/>
                                    </div>
                                </div>   
                                <!--<div class="mb-3 row">-->
                                <!--    <label for="net_duration" class="col-sm-12 col-lg-2 col-form-label">Net Duration <small>(minutes)</small>: </label>-->
                                <!--    <div class="col-lg-10 col-sm-12 d-flex align-items-center">-->
                                <!--        <input type="text" class="form-control" id="net_duration" name="net_duration" required/>-->
                                <!--    </div>-->
                                <!--</div>   -->
                                <div class="mb-3 row">
                                    <label for="benefits" class="col-sm-12 col-lg-2 col-form-label">Benefits: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <textarea class="form-control" id="benefits" name="benefits" rows="7" required>
[
    {
        "name": "strengths"
    },
    {
        "name": "stamina"
    }
]
                                        </textarea>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="session_details" class="col-sm-12 col-lg-2 col-form-label">Session Details: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <textarea class="form-control" id="session_details" name="session_details" rows="7" required>
[
    {
        "name": "test name 1",
        "duration": "5"
    },
    {
        "name": "test name 2",
        "duration": "8"
    }
]
                                        </textarea>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="video_url" class="col-sm-12 col-lg-2 col-form-label">Video: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="file" class="form-control" id="video_url" name="video_url" required/>
                                    </div>
                                </div>    
                                <div class="mb-3 row">
                                    <label for="image_url" class="col-sm-12 col-lg-2 col-form-label">Image: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="file" class="form-control" id="image_url" name="image_url" required/>
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
