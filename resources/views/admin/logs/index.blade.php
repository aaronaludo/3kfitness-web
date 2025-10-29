@extends('layouts.admin')
@section('title', 'Logs')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Logs</h2></div>
                <div class="d-flex align-items-center">
                    <form action="{{ route('admin.logs.print') }}" method="POST" id="print-form">
                        @csrf
                        <button class="btn btn-danger ms-2" type="submit" id="print-submit-button">
                            <i class="fa-solid fa-print"></i>&nbsp;&nbsp;&nbsp;
                            <span id="print-loader" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Print
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-lg-12 mb-20">
                <div class="box">
                    <form action="{{ route('admin.logs.index') }}" method="GET">
                        <div class="row">
                            <div class="col-lg-5">
                                <div class="input-group mb-3 mb-lg-0">
                                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" class="form-control" placeholder="Search by Message" name="search" value="{{ request('search') }}"/>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <select name="search_column" class="form-select mb-3">
                                    <option value="" {{ request('search_column') == '' ? '' : 'selected' }}>All</option>
                                    <option value="Admin" {{ request('search_column') == 'Admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="Staff" {{ request('search_column') == 'Staff' ? 'selected' : '' }}>Staff</option>
                                    <option value="Member" {{ request('search_column') == 'Member' ? 'selected' : '' }}>Member</option>
                                    <option value="Trainer" {{ request('search_column') == 'Trainer' ? 'selected' : '' }}>Trainer</option>
                                </select>
                            </div>
                            <div class="col-lg-2">
                                <select name="sort_column" class="form-select mb-3">
                                    <option value="DESC" {{ request('sort_column') == 'DESC' ? 'selected' : '' }}>Descending</option>
                                    <option value="ASC" {{ request('sort_column') == 'ASC' ? 'selected' : '' }}>Ascending</option>
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <button class="btn btn-danger w-100" type="submit">Search</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="sortable" data-column="id">ID <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="message">Message <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="role_name">Role Name <i class="fa fa-sort"></i></th>
                                            <th class="sortable" data-column="created_date">Created Date <i class="fa fa-sort"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-body">
                                        @foreach($data as $item)
                                            <tr>
                                                <td>{{ $item->id  }}</td>
                                                <td>{{ $item->message }}</td>
                                                <td>{{ $item->role_name }}</td>
                                                <td>{{ $item->created_at }}</td>
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
