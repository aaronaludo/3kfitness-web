@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-between">
                <div><h2 class="title">Dashboard</h2></div>
            </div>
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Members</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-user"></i>
                                <h2 class="float-end">{{ $gym_members_count }}</h2>
                            </div>
                            <div class="tile-footer"><a href="#">View more...</a></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Total Staffs</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-heart"></i>
                                <h2 class="float-end">{{ $staffs_count }}</h2>
                            </div>
                            <div class="tile-footer"><a href="#">View more...</a></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Total Feedbacks</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-message"></i>
                                <h2 class="float-end">{{ $feedbacks_count }}</h2>
                            </div>
                            <div class="tile-footer"><a href="#">View more...</a></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Memberships</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-user"></i>
                                <h2 class="float-end">{{ $memberships_count }}</h2>
                            </div>
                            <div class="tile-footer"><a href="#">View more...</a></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Classes</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-user"></i>
                                <h2 class="float-end">{{ $classes_count }}</h2>
                            </div>
                            <div class="tile-footer"><a href="#">View more...</a></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Pending User Memberships</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-user"></i>
                                <h2 class="float-end">{{ $user_membership_count }}</h2>
                            </div>
                            <div class="tile-footer"><a href="#">View more...</a></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="box">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="d-flex justify-content-between">
                                <h5>Latest Logs</h5>
                                <a href="{{ route('admin.logs.index') }}">see more</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <th>ID</th>
                                        <th>Message</th>
                                        <th>Role Name</th>
                                        <th>Created Date</th>
                                    </thead>
                                    <tbody>
                                        @foreach($logs as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ $item->message }}</td>
                                                <td>{{ $item->role_name }}</td>
                                                <td>{{ $item->created_at }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-12 my-3">
                <div class="box">
                    <canvas id="gymChart" width="400" height="200"></canvas>
                </div>
            </div>
            <!-- Membership Data Chart -->
            <div class="col-lg-6 col-12 my-3">
                <div class="box">
                    <canvas id="membershipDataChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Financial Summaries Chart -->
            <div class="col-lg-6 col-12 my-3">
                <div class="box">
                    <canvas id="financialSummariesChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Notifications Chart -->
            <div class="col-lg-6 col-12 my-3">
                <div class="box">
                    <canvas id="notificationsChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    <script>
        var ctx = document.getElementById('gymChart').getContext('2d');
        var gymChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June'], // Monthly Labels
                datasets: [
                    {
                        label: 'New Memberships',
                        data: [30, 45, 60, 40, 50, 70], // Sample Data
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Membership Renewals',
                        data: [20, 30, 40, 35, 45, 60], // Sample Data
                        backgroundColor: 'rgba(255, 206, 86, 0.2)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Class Participation',
                        data: [50, 60, 80, 90, 100, 110], // Sample Data
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Gym Activity Overview'
                    }
                }
            }
        });
    </script>
    <script>
        // Membership Data Chart
        var membershipCtx = document.getElementById('membershipDataChart').getContext('2d');
        var membershipDataChart = new Chart(membershipCtx, {
            type: 'bar',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June'], // Membership months
                datasets: [{
                    label: 'New Members',
                    data: [20, 30, 25, 35, 40, 45], // Example static data
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Membership Data'
                    }
                }
            }
        });

        // Financial Summaries Chart
        var financialCtx = document.getElementById('financialSummariesChart').getContext('2d');
        var financialSummariesChart = new Chart(financialCtx, {
            type: 'line',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June'], // Financial months
                datasets: [{
                    label: 'Revenue ($)',
                    data: [5000, 7000, 8000, 7500, 9000, 10000], // Example static data
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Financial Summaries'
                    }
                }
            }
        });

        // Notifications Chart
        var notificationsCtx = document.getElementById('notificationsChart').getContext('2d');
        var notificationsChart = new Chart(notificationsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Unread', 'Read', 'Resolved'], // Notification statuses
                datasets: [{
                    label: 'Notifications',
                    data: [60, 30, 10], // Example static data
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Notifications Overview'
                    }
                }
            }
        });
    </script>
@endsection
