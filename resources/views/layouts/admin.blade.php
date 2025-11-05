<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive.css') }}" />
    <style>
        .sortable {
            cursor: pointer;
            user-select: none;
        }
    </style>
    <title>@yield('title')</title>
    @yield('styles')
</head>
<body>
    <div id="wrapper">
        <header id="header" class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid p-0">
                <div id="header-logo">
                    <div class="d-flex justify-content-center align-items-center h-100 w-100">
                        {{-- <img src="assets/images/logo-with-text.png" alt="Mobvex"/> --}}
                        <h5 class="m-0" style="color: #3f1214 !important;font-weight: 1000;">3KFITNESS</h5>
                    </div>
                </div>
                
                <a href="#" id="button-menu"><i class="fa-solid fa-bars"></i></a>
                <a href="#" id="button-menu-close"><i class="fa-solid fa-xmark"></i></a>

                @if(auth()->guard('admin')->user()->role_id == 2)
                    @php
                        $currentUserId = auth()->guard('admin')->user()->id;
                        $today = now()->toDateString();
                        $payroll = \App\Models\Payroll::where('user_id', $currentUserId)
                                    ->whereDate('clockin_at', $today)
                                    ->first();
                    @endphp
                
                    <div class="d-flex align-items-center ms-auto">
                        @if(!$payroll)
                            <button type="button" class="btn btn-sm btn-success me-4" data-bs-toggle="modal" data-bs-target="#confirmationModal" data-action="{{ route('admin.payrolls.clockin') }}" data-message="Are you sure you want to Clock In?">
                                Clock In
                            </button>
                        @else
                            @if(is_null($payroll->clockout_at))
                                <button type="button" class="btn btn-sm btn-danger me-4" data-bs-toggle="modal" data-bs-target="#confirmationModal" data-action="{{ route('admin.payrolls.clockout') }}" data-message="Are you sure you want to Clock Out?">
                                    Clock Out
                                </button>
                            @else
                                <span class="text-success fw-bold me-4">Already Clocked Out</span>
                            @endif
                        @endif
                    </div>
                @endif
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        @php
                            $user = auth()->guard('admin')->user();
                            $roleMap = [1 => 'Admin', 2 => 'Staff', 4 => 'Super Admin'];
                            $roleLabel = $roleMap[$user->role_id] ?? 'User';
                            $profilePicture = $user->profile_picture ? asset($user->profile_picture) : asset('assets/images/profile-45x45.png');
                        @endphp

                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ $profilePicture }}"
                                alt="User" title="User"
                                class="rounded-circle me-2" width="32" height="32" />
                            {{ $user->first_name }} {{ $user->last_name }}
                            <span class="badge bg-secondary ms-2">{{ $roleLabel }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                            <li><a class="dropdown-item" href="{{ route('admin.edit-profile') }}">Edit Profile</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.change-password') }}">Change Password</a></li>
                            <li>
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </header>
        <nav id="column-left">
            <ul id="menu">
                <li>
                    <a href="{{ route('admin.dashboard.index') }}" class="{{ request()->routeIs('admin.dashboard.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-gauge"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a class="collapsed {{ 
                        Request::route()->getName() === 'admin.gym-management.index' || 
                        Request::route()->getName() === 'admin.gym-management.schedules' || 
                        Request::route()->getName() === 'admin.gym-management.members' || 
                        Request::route()->getName() === 'admin.staff-account-management.memberships' || 
                        Request::route()->getName() === 'admin.staff-account-management.membership-payments'
                        ? 'active' : '' 
                    }}" 
                    data-bs-toggle="collapse" href="#gym-management-menu" 
                    role="button" 
                    aria-expanded="{{ 
                        Request::route()->getName() === 'admin.gym-management.index' || 
                        Request::route()->getName() === 'admin.gym-management.schedules' || 
                        Request::route()->getName() === 'admin.gym-management.members' || 
                        Request::route()->getName() === 'admin.staff-account-management.memberships' || 
                        Request::route()->getName() === 'admin.staff-account-management.membership-payments'
                        ? 'true' : 'false' 
                    }}" 
                    aria-controls="gym-management-menu">
                        <i class="fa-solid fa-dumbbell"></i> Gym Management
                    </a>
                
                    <ul id="gym-management-menu" class="collapse {{ 
                        Request::route()->getName() === 'admin.gym-management.schedules' || 
                        Request::route()->getName() === 'admin.gym-management.members' || 
                        Request::route()->getName() === 'admin.staff-account-management.memberships' || 
                        Request::route()->getName() === 'admin.staff-account-management.membership-payments'
                        ? 'show' : '' 
                    }}">
                        <li>
                            <a href="{{ route('admin.gym-management.schedules') }}" 
                               class="{{ Request::route()->getName() === 'admin.gym-management.schedules' ? 'active' : '' }}">
                               Classes
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.gym-management.members') }}" 
                               class="{{ Request::route()->getName() === 'admin.gym-management.members' ? 'active' : '' }}">
                               Members Data
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.staff-account-management.memberships') }}" 
                               class="{{ Request::route()->getName() === 'admin.staff-account-management.memberships' ? 'active' : '' }}">
                               Memberships
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.staff-account-management.membership-payments') }}" 
                               class="{{ Request::route()->getName() === 'admin.staff-account-management.membership-payments' ? 'active' : '' }}">
                               Membership Payments
                            </a>
                        </li>
                    </ul>
                </li>                
                <li>
                    <a class="collapsed {{ 
                        in_array(Request::route()->getName(), [
                            'admin.staff-account-management.index', 
                            'admin.staff-account-management.attendances'
                        ]) ? 'active' : '' }}" 
                        data-bs-toggle="collapse" href="#staff-account-management-menu" 
                        role="button" aria-expanded="{{ 
                        in_array(Request::route()->getName(), [
                            'admin.staff-account-management.index', 
                            'admin.staff-account-management.attendances'
                        ]) ? 'true' : 'false' }}" 
                        aria-controls="staff-account-management-menu">
                        <i class="fa-solid fa-users"></i> Staff Account Management
                    </a>
                    <ul id="staff-account-management-menu" class="collapse {{ 
                        in_array(Request::route()->getName(), [
                            'admin.staff-account-management.index', 
                            'admin.staff-account-management.attendances'
                        ]) ? 'show' : '' }}">
                        @if(auth()->guard('admin')->user()->role_id != 2)
                            <li>
                                <a href="{{ route('admin.staff-account-management.index') }}" 
                                   class="{{ Request::route()->getName() === 'admin.staff-account-management.index' ? 'active' : '' }}">
                                   Overview
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ route('admin.staff-account-management.attendances') }}" 
                               class="{{ Request::route()->getName() === 'admin.staff-account-management.attendances' ? 'active' : '' }}">
                               Attendances
                            </a>
                        </li>
                    </ul>
                </li>
                @php
                    $trainerRoutes = [
                        'admin.trainer-management.index',
                        'admin.trainer-management.add',
                        'admin.trainer-management.edit',
                        'admin.trainer-management.view',
                    ];
                @endphp

                <li>
                    <a class="collapsed {{ in_array(Request::route()->getName(), $trainerRoutes) ? 'active' : '' }}" 
                    data-bs-toggle="collapse" 
                    href="#trainer-management-menu" 
                    role="button" 
                    aria-expanded="{{ in_array(Request::route()->getName(), $trainerRoutes) ? 'true' : 'false' }}" 
                    aria-controls="trainer-management-menu">
                        <i class="fa-solid fa-dumbbell"></i> Trainer Management
                    </a>
                    <ul id="trainer-management-menu" class="collapse {{ in_array(Request::route()->getName(), $trainerRoutes) ? 'show' : '' }}">
                        <li><a href="{{ route('admin.trainer-management.index') }}" class="{{ Request::route()->getName() === 'admin.trainer-management.index' ? 'active' : '' }}">Trainers</a></li>
                        {{-- <li><a href="{{ route('admin.trainer-management.add') }}" class="{{ Request::route()->getName() === 'admin.trainer-management.add' ? 'active' : '' }}">Add Trainer</a></li>
                        <li><a href="#" class="{{ Request::route()->getName() === 'admin.trainer-management.edit' ? 'active' : '' }}">Edit Trainer</a></li>
                        <li><a href="#" class="{{ Request::route()->getName() === 'admin.trainer-management.view' ? 'active' : '' }}">View Trainer</a></li> --}}
                    </ul>
                </li>
                @if(auth()->guard('admin')->user()->role_id != 2)
                    <li>
                        <a class="collapsed {{ 
                            in_array(Request::route()->getName(), [
                                'admin.banners.index',
                                'admin.logs.index'
                            ]) ? 'active' : '' }}" 
                            data-bs-toggle="collapse" 
                            href="#operations-menu" 
                            role="button" 
                            aria-expanded="{{ 
                                in_array(Request::route()->getName(), [
                                    'admin.banners.index',
                                    'admin.logs.index'
                                ]) ? 'true' : 'false' }}" 
                            aria-controls="operations-menu">
                            <i class="fa-solid fa-cogs"></i> Operations
                        </a>
                        <ul id="operations-menu" class="collapse {{ 
                            in_array(Request::route()->getName(), [
                                'admin.banners.index',
                                'admin.logs.index'
                            ]) ? 'show' : '' }}">
                            <li><a href="{{ route('admin.banners.index') }}" class="{{ Request::route()->getName() === 'admin.banners.index' ? 'active' : '' }}">Banners</a></li>
                            <li><a href="{{ route('admin.logs.index') }}" class="{{ Request::route()->getName() === 'admin.logs.index' ? 'active' : '' }}">Logs</a></li>
                        </ul>
                    </li>
                @endif
                @if(auth()->guard('admin')->user()->role_id != 2)
                <li><a href="{{ route('admin.payrolls.index') }}"><i class="fa-solid fa-money-bill"></i> Payrolls</a></li>
                @endif
                <!--<li>-->
                <!--    <a href="{{ route('admin.banners.index') }}">-->
                <!--        <i class="fa-solid fa-user"></i> Banners-->
                <!--    </a>-->
                <!--</li>-->
                
                <!--<li>-->
                <!--    <a href="{{ route('admin.goals.index') }}">-->
                <!--        <i class="fa-solid fa-user"></i> Goals-->
                <!--    </a>-->
                <!--</li>-->
                
                <!--<li>-->
                <!--    <a href="{{ route('admin.popular-workouts.index') }}">-->
                <!--        <i class="fa-solid fa-user"></i> Popular Workout-->
                <!--    </a>-->
                <!--</li>-->
                
                <!--<li>-->
                <!--    <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">-->
                <!--        <i class="fa-solid fa-chart-simple"></i> Reports-->
                <!--    </a>-->
                <!--</li>-->
                
                <!--<li>-->
                <!--    <a href="{{ route('admin.online-registrations.index') }}" class="{{ request()->routeIs('admin.online-registrations.index') ? 'active' : '' }}">-->
                <!--        <i class="fa-solid fa-address-card"></i> Online Registrations-->
                <!--    </a>-->
                <!--</li>-->
                
                <!--<li>-->
                <!--    <a href="{{ route('admin.feedbacks.index') }}" class="{{ request()->routeIs('admin.feedbacks.index') ? 'active' : '' }}">-->
                <!--        <i class="fa-solid fa-comment"></i> Feedbacks-->
                <!--    </a>-->
                <!--</li>   -->
                
                <!--<li>-->
                <!--    <a href="{{ route('admin.logs.index') }}" class="{{ request()->routeIs('admin.logs.index') ? 'active' : '' }}">-->
                <!--        <i class="fa-solid fa-list"></i> Logs-->
                <!--    </a>-->
                <!--</li> -->
            </ul>
        </nav>
        <div id="content">
            @yield('content')
        </div>
        <footer>Copyright. &copy; 2024 All Rights Reserved.</footer>
    </div>

    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalMessage">
                    Are you sure?
                    {{-- add current time here --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="confirmForm" method="POST" action="">
                        @csrf
                        <button type="submit" class="btn btn-primary">Confirm</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        var confirmationModal = document.getElementById('confirmationModal');
    
        confirmationModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; 
            var actionUrl = button.getAttribute('data-action'); 
            var message = button.getAttribute('data-message'); 
    
            var modalMessage = confirmationModal.querySelector('#modalMessage');
            var confirmForm = confirmationModal.querySelector('#confirmForm');
    
            var now = new Date();
            var formattedTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    
            modalMessage.innerHTML = `${message}<br><strong>Current Time:</strong> ${formattedTime}`;
            confirmForm.action = actionUrl;
        });
    </script>    

    @yield('scripts')
    <script type="text/javascript" src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/script.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/print-form.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/sorting.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/reveal-button.js') }}"></script>
</body>
</html>
