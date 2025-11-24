@extends('layouts.admin')
@section('title', 'Edit Profile')

@section('content')
    @php
        $adminUser = auth()->guard('admin')->user();
        $currentProfilePicture = $adminUser->profile_picture ?? null;
        $currentProfilePictureUrl = $currentProfilePicture ? asset($currentProfilePicture) : asset('assets/images/profile-45x45.png');
    @endphp

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Edit Profile</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-lg-12">
                            <form action="{{ route('admin.account.update-profile') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3 row">
                                    <label for="profile_picture" class="col-sm-12 col-lg-2 col-form-label">Profile Picture:</label>
                                    <div class="col-lg-10 col-sm-12">
                                        <div class="d-flex align-items-center flex-wrap gap-3">
                                            <img
                                                id="profilePreview"
                                                src="{{ $currentProfilePictureUrl }}"
                                                alt="Profile preview"
                                                class="rounded-circle border"
                                                width="100"
                                                height="100"
                                                data-default="{{ asset('assets/images/profile-45x45.png') }}"
                                                data-existing="{{ $currentProfilePicture ? asset($currentProfilePicture) : '' }}"
                                                data-has-existing="{{ $currentProfilePicture ? '1' : '0' }}"
                                            />
                                            <div class="flex-grow-1" style="max-width: 320px;">
                                                <input
                                                    type="file"
                                                    class="form-control mb-2"
                                                    id="profile_picture"
                                                    name="profile_picture"
                                                    accept="image/*"
                                                />
                                                <input type="hidden" name="remove_profile_picture" id="remove_profile_picture" value="0">
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" id="removeProfileButton">
                                                        Remove Photo
                                                    </button>
                                                </div>
                                                <small class="text-muted d-block mt-2">
                                                    Accepted formats: JPG, PNG, GIF up to 2MB.
                                                </small>
                                            </div>
                                        </div>
                                        @error('profile_picture')
                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="first_name" class="col-sm-12 col-lg-2 col-form-label">First name: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $adminUser->first_name) }}"/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="last_name" class="col-sm-12 col-lg-2 col-form-label">Last name: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $adminUser->last_name) }}"/>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="address" class="col-sm-12 col-lg-2 col-form-label">Address: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $adminUser->address) }}"/>
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
                                            value="{{ old('phone_number', $adminUser->phone_number) }}"
                                            required
                                        />
                                        <div class="invalid-feedback">
                                            Please enter a valid Philippine mobile number (e.g., +639123456789).
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="email" class="col-sm-12 col-lg-2 col-form-label">Email: <span class="required">*</span></label>
                                    <div class="col-lg-10 col-sm-12 d-flex align-items-center">
                                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $adminUser->email) }}"/>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center mt-5 mb-4">
                                    <button type="submit" class="btn btn-danger">Submit</button>
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
            const fileInput = document.getElementById('profile_picture');
            const previewImg = document.getElementById('profilePreview');
            const removeButton = document.getElementById('removeProfileButton');
            const removeInput = document.getElementById('remove_profile_picture');

            if (!fileInput || !previewImg || !removeButton || !removeInput) {
                return;
            }

            const defaultImage = previewImg.dataset.default;
            const existingImage = previewImg.dataset.existing || defaultImage;

            const setRemoveButtonState = () => {
                const hasExisting = previewImg.dataset.hasExisting === '1';
                if (!hasExisting && !fileInput.files.length) {
                    removeButton.disabled = true;
                } else {
                    removeButton.disabled = false;
                }
            };

            setRemoveButtonState();

            fileInput.addEventListener('change', (event) => {
                const [file] = event.target.files;

                if (file) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        previewImg.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                    removeInput.value = 0;
                } else {
                    if (previewImg.dataset.hasExisting === '1') {
                        previewImg.src = existingImage;
                        removeInput.value = 0;
                    } else {
                        previewImg.src = defaultImage;
                        removeInput.value = 0;
                    }
                }

                setRemoveButtonState();
            });

            removeButton.addEventListener('click', () => {
                if (fileInput.files.length) {
                    fileInput.value = '';
                    if (previewImg.dataset.hasExisting === '1') {
                        previewImg.src = existingImage;
                        removeInput.value = 0;
                    } else {
                        previewImg.src = defaultImage;
                        removeInput.value = 0;
                    }
                    setRemoveButtonState();
                    return;
                }

                if (previewImg.dataset.hasExisting === '1') {
                    previewImg.src = defaultImage;
                    previewImg.dataset.hasExisting = '0';
                    removeInput.value = 1;
                    setRemoveButtonState();
                }
            });
        });
    </script>
@endsection
