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
                            <div class="tile-footer"><a href="{{ route('admin.gym-management.members') }}">View more...</a></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Total Staff</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-id-badge"></i>
                                <h2 class="float-end">{{ $staffs_count }}</h2>
                            </div>
                            <div class="tile-footer"><a href="{{ route('admin.staff-account-management.index') }}">View more...</a></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Total Feedbacks</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-message"></i>
                                <h2 class="float-end">{{ $feedbacks_count }}</h2>
                            </div>
                            <div class="tile-footer"><a href="{{ route('admin.feedbacks.index') }}">View more...</a></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Memberships</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-id-card"></i>
                                <h2 class="float-end">{{ $memberships_count }}</h2>
                            </div>
                            <div class="tile-footer"><a href="{{ route('admin.staff-account-management.memberships') }}">View more...</a></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Classes</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-calendar"></i>
                                <h2 class="float-end">{{ $classes_count }}</h2>
                            </div>
                            <div class="tile-footer"><a href="{{ route('admin.gym-management.schedules') }}">View more...</a></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="tile tile-primary">
                            <div class="tile-heading">Pending User Memberships</div>
                            <div class="tile-body">
                                <i class="fa-regular fa-clock"></i>
                                <h2 class="float-end">{{ $user_membership_count }}</h2>
                            </div>
                            <div class="tile-footer"><a href="{{ route('admin.staff-account-management.user-memberships') }}">View more...</a></div>
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
                                                <td>{{ optional($item->created_at)->format('F j, Y g:iA') }}</td>
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
                    <div style="height:300px"><canvas id="gymChart"></canvas></div>
                </div>
            </div>
            <!-- Membership Data Chart -->
            <div class="col-lg-6 col-12 my-3">
                <div class="box">
                    <div style="height:300px"><canvas id="membershipDataChart"></canvas></div>
                </div>
            </div>

            <!-- Financial Summaries Chart -->
            <div class="col-lg-6 col-12 my-3">
                <div class="box">
                    <div style="height:300px"><canvas id="financialSummariesChart"></canvas></div>
                </div>
            </div>

            <!-- Notifications Chart -->
            <div class="col-lg-6 col-12 my-3">
                <div class="box">
                    <div style="height:300px"><canvas id="notificationsChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const labels = @json($chartLabels ?? []);
        const membersPerMonth = @json($membersPerMonth ?? []);
        const membershipsPerMonth = @json($membershipsPerMonth ?? []);
        const classesPerMonth = @json($classesPerMonth ?? []);
        const approvedUserMembershipsPerMonth = @json($approvedUserMembershipsPerMonth ?? []);

        var ctx = document.getElementById('gymChart').getContext('2d');
        var gymChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Members',
                        data: membersPerMonth,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Memberships',
                        data: membershipsPerMonth,
                        backgroundColor: 'rgba(255, 206, 86, 0.2)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Classes',
                        data: classesPerMonth,
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
                    },
                    legend: { display: true }
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
                labels: labels,
                datasets: [{
                    label: 'New Memberships',
                    data: membershipsPerMonth,
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
                        text: 'Memberships (Last 6 Months)'
                    }
                }
            }
        });

        // Financial Summaries Chart
        var financialCtx = document.getElementById('financialSummariesChart').getContext('2d');
        var financialSummariesChart = new Chart(financialCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Approved User Memberships',
                    data: approvedUserMembershipsPerMonth,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    tension: 0.25
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
                        text: 'Approved User Memberships (Last 6 Months)'
                    }
                }
            }
        });

        // Notifications Chart
        var notificationsCtx = document.getElementById('notificationsChart').getContext('2d');
        var notificationsChart = new Chart(notificationsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Members', 'Staff', 'Pending Memberships'],
                datasets: [{
                    label: 'Current Overview',
                    data: [
                        {{ (int) ($gym_members_count ?? 0) }},
                        {{ (int) ($staffs_count ?? 0) }},
                        {{ (int) ($user_membership_count ?? 0) }}
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(255, 206, 86, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 159, 64, 1)',
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
                        text: 'Current Overview'
                    }
                }
            }
        });
    </script>
@endsection
