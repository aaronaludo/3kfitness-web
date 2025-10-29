@extends('layouts.admin')
@section('title', 'Admins')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Admins</h1></div>
                @if (auth()->guard('admin')->user()->role_id === 4)
                    <div class="d-flex align-items-center"><a class="btn btn-primary" href="{{ route('admin.admins.add') }}"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;&nbsp;Add Admin</a></div>
                @endif
            </div>
            <div class="col-lg-12 mb-20">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-10">
                            <div class="input-group mb-3 mb-lg-0">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" class="form-control" placeholder="Search by Name" />
                            </div>
                        </div>
                        <div class="col-lg-2"><button class="btn btn-primary w-100">Search</button></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Contact Number</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($users as $user)
                                            <tr>
                                                <td>{{ $user->id }}</td>
                                                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                                                <td>{{ $user->role->name }}</td>
                                                <td>{{ $user->phone_number }}</td>
                                                <td>{{ $user->status->name }}</td>
                                                <td>
                                                    <div class="d-flex">
                                                        <div class="action-button"><a href="{{ route('admin.admins.view', $user->id) }}" title="View"><i class="fa-solid fa-eye"></i></a></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection