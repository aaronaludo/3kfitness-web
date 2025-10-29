@extends('layouts.admin')
@section('title', 'Payrolls')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Payrolls</h2></div>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-danger ms-2">
                        <i class="fa-solid fa-print"></i>&nbsp;&nbsp;&nbsp;Print
                    </button>
                </div>
            </div>
            <div class="col-lg-12 mb-20">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-10">
                            <form action="#" method="GET" class="d-flex">
                                <div class="input-group mb-3 mb-lg-0 w-100">
                                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" class="form-control" name="member_name" placeholder="Search" />
                                </div>
                        </div>
                        <div class="col-lg-2">
                            <button type="submit" class="btn btn-danger w-100">Search</button>
                        </div>
                            </form>
                    </div>
                </div>
            </div>            
            <div class="col-lg-12">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <th>ID</th>
                                        <th>Member Name</th>
                                        <th>Clock In Date</th>
                                        <th>Clock Out Date</th>
                                        <th>Total Hours</th>
                                        <th>Total Amount</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </thead>
                                    <tbody>
                                        @foreach($data as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ $item->user->first_name }} {{ $item->user->last_name }}</td>
                                                <td>{{ $item->clockin_at }}</td>
                                                <td>{{ $item->clockout_at }}</td>
                                                <td>
                                                    {{ $item->clockout_at 
                                                        ? \Carbon\Carbon::parse($item->clockin_at)->diffInHours(\Carbon\Carbon::parse($item->clockout_at)) 
                                                        : 'Wait for clockout' }}
                                                </td>
                                                <td>
                                                    {{
                                                        $item->clockout_at 
                                                        ? "₱" . \Carbon\Carbon::parse($item->clockin_at)->diffInHours(\Carbon\Carbon::parse($item->clockout_at)) * $item->user->rate_per_hour
                                                        : 'Wait for clockout'
                                                    }}
                                                </td>
                                                <td>{{ $item->created_at }}</td>
                                                <td>
                                                    <div class="d-flex">
                                                        <div class="action-button"><a href="{{ route('admin.payrolls.view', $item->id) }}" title="View"><i class="fa-solid fa-eye"></i></a></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                {{ $data->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
