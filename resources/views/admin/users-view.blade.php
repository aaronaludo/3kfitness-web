@extends('layouts.admin')
@section('title', 'Admin View')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">View</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="alert" style="background: #b3ccf5;">
                    <h4 class="alert-heading color-kabarkadogs">Full name: <span class="fw-bold ms-2">{{ $user->first_name }} {{ $user->last_name }}</span></h4>
                    <p class="color-kabarkadogs">Address: <span class="fw-bold ms-2">{{ $user->address }}</span></p>
                    <p class="color-kabarkadogs">Phone number: <span class="fw-bold ms-2">{{ $user->phone_number }}</span></p>
                    <p class="color-kabarkadogs">Email: <span class="fw-bold ms-2">{{ $user->email }}</span></p>
                    <p class="color-kabarkadogs">Role: <span class="fw-bold ms-2">{{ $user->role->name }}</span></p>
                    <p class="color-kabarkadogs">Status: <span class="fw-bold ms-2">{{ $user->status->name }}</span></p>
                    @if ($user->role->id === 2)
                        <img src="{{ asset('storage/' . $user->driver_information->license) }}" alt="license" height="300" class="mb-5 rounded">
                        <form action="{{ route('admin.users.verify', $user->id) }}" method="POST">
                            @csrf
                            <div>
                                <button class="btn btn-success" type="submit" name="status_id" value="2" {{ $user->status_id == 2 ? 'disabled' : ''}}>
                                    Registered
                                </button>
                                <button class="btn btn-danger" type="submit" name="status_id" value="3" {{ $user->status_id == 3 ? 'disabled' : ''}}>
                                    Failed
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>                    
        </div>
    </div>
@endsection