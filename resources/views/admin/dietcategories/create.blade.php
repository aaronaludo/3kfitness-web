@extends('layouts.admin')
@section('title', 'Create Diet Categories')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Create Diet Categories</h2></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.diet-categories.store') }}" method="POST" enctype="multipart/form-data" id="dietCategoryForm">
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
                                    <label for="description" class="col-sm-12 col-lg-2 col-form-label">Description: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <textarea class="form-control" id="description" name="description" rows="7" required></textarea>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="protein" class="col-sm-12 col-lg-2 col-form-label">Protein: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="protein" name="protein" required/>
                                    </div>
                                </div>   
                                <div class="mb-3 row">
                                    <label for="fat" class="col-sm-12 col-lg-2 col-form-label">Fat: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="fat" name="fat" required/>
                                    </div>
                                </div>   
                                <div class="mb-3 row">
                                    <label for="calories" class="col-sm-12 col-lg-2 col-form-label">Calories: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="calories" name="calories" required/>
                                    </div>
                                </div>   
                                <div class="mb-3 row">
                                    <label for="ingredients" class="col-sm-12 col-lg-2 col-form-label">Ingredients: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <textarea class="form-control" id="ingredients" name="ingredients" rows="7" required>
[
    {
        "name": "1.5 cup levelled whole"
    },
    {
        "name": "1.5 pinch salt"
    }
]
                                        </textarea>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="recipe_description" class="col-sm-12 col-lg-2 col-form-label">Recipe Description: </label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <textarea class="form-control" id="recipe_description" name="recipe_description" rows="7" required></textarea>
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
        document.getElementById('dietCategoryForm').addEventListener('submit', function(e) {
            const submitButton = document.getElementById('submitButton');
            const loader = document.getElementById('loader');

            // Disable the button and show loader
            submitButton.disabled = true;
            loader.classList.remove('d-none');
        });
    </script>
@endsection
