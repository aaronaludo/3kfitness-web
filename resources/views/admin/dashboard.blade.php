@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Dashboard</h1></div>
            </div>
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Total Users</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-file"></i>
                                <h2 class="float-end">{{ $users }}</h2>
                            </div>
                            <div class="tile-footer"><a href="{{ route('admin.users.index') }}">View more...</a></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Total Admins</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-file"></i>
                                <h2 class="float-end">{{ $admins }}</h2>
                            </div>
                            <div class="tile-footer"><a href="{{ route('admin.admins.index') }}">View more...</a></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Total Ride Histories</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-file"></i>
                                <h2 class="float-end">{{ $histories }}</h2>
                            </div>
                            <div class="tile-footer"><a href="{{ route('admin.ride-histories.index') }}">View more...</a></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <h5>Latest Ride Histories</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <th>ID</th>
                                        <th>Passenger Name</th>
                                        <th>Driver Name</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($latestHistories as $history)
                                        <tr>
                                            <td>{{ $history->id }}</td>
                                            <td>{{ $history->passenger->first_name }} {{ $history->passenger->last_name }}</td>
                                            <td>{{ $history->driver->first_name }} {{ $history->driver->last_name }}</td>
                                            <td>{{ \Carbon\Carbon::parse($history->created_at)->format('m/d/Y') }}</td>
                                            <td>
                                                <div class="d-flex">
                                                    <div class="action-button"><a href="{{ route('admin.ride-histories.view', $history->id) }}" title="View"><i class="fa-solid fa-eye"></i></a></div>
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