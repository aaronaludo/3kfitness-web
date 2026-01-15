<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="print-preview-route" content="{{ route('admin.print.preview') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive.css') }}" />
    <style>
        .sortable {
            cursor: pointer;
            user-select: none;
        }

        #header.admin-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8787 45%, #ffe066 100%);
            box-shadow: 0 12px 30px rgba(255, 118, 118, 0.25);
            border-bottom: none;
            position: sticky !important;
            top: 0;
            z-index: 1030;
        }

        #wrapper {
            overflow: visible !important; /* allow sticky header to work while sidebar stays fixed */
        }

        #header-logo-img {
            max-height: 48px;
            width: auto;
            border-radius: 12px;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
        }

        #header-logo .logo-fallback-text {
            font-weight: 800;
            color: #fff;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        #header-logo .logo-tagline {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.85);
            text-transform: uppercase;
        }

        #header .nav-link,
        #header .btn,
        #header .badge {
            z-index: 1;
        }

        #header::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.08);
            pointer-events: none;
            mix-blend-mode: soft-light;
        }

        #header {
            height: auto;
            padding: 8px 12px;
        }

        #header > .container-fluid {
            position: relative;
            z-index: 1;
            flex-wrap: wrap;
            row-gap: 8px;
        }

        .header-actions {
            gap: 12px;
            min-width: 0;
            flex: 1 1 auto;
            justify-content: flex-end;
            flex-wrap: wrap;
            row-gap: 8px;
        }

        .datetime-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.88));
            color: #273341;
            font-size: 0.8rem;
            font-weight: 700;
            border: 1px solid rgba(255, 107, 107, 0.35);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
        }

        .datetime-chip__icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255, 107, 107, 0.12);
            color: #e24949;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .datetime-chip__meta {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            gap: 2px;
        }

        .datetime-chip .live-clock {
            letter-spacing: 0.04em;
            font-weight: 800;
        }

        .datetime-chip .live-date {
            font-weight: 600;
            color: #5c6673;
            font-size: 0.68rem;
            text-transform: uppercase;
        }

        .header-calendar-link {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.68rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .header-calendar-link__icon {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.25);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .header-calendar-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.26);
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
        }

        .header-actions .navbar-nav {
            flex: 0 0 auto;
            margin-top: 0 !important;
        }

        .time-action-trigger {
            border-radius: 999px;
            padding: 7px 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #ffffff;
            color: #333;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
        }

        .time-action-trigger i {
            color: #0fbf83;
        }

        .staff-time-card {
            background: linear-gradient(135deg, rgba(15, 191, 131, 0.12), rgba(15, 191, 131, 0.05));
            border: 1px solid rgba(15, 191, 131, 0.25);
            border-radius: 16px;
            padding: 16px;
        }

        .staff-time-card__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .staff-time-card__clock {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .staff-time-card__clock-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 0 0 1px rgba(15, 191, 131, 0.25);
            color: #0fbf83;
        }

        .staff-time-card__time {
            font-weight: 800;
            font-size: 1.05rem;
        }

        .staff-time-card__meta {
            font-size: 0.82rem;
            color: #5b646e;
        }

        .staff-time-card__actions {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            border: 1px solid rgba(0, 0, 0, 0.08);
            color: #0f5c3f;
            background: rgba(15, 191, 131, 0.15);
        }

        .status-pill--pending {
            background: rgba(0, 0, 0, 0.05);
            color: #444;
        }

        .status-pill--done {
            background: rgba(33, 150, 243, 0.12);
            color: #1a4f7a;
        }

        .time-action-btn {
            border-radius: 12px;
            font-weight: 700;
            padding: 0.45rem 0.95rem;
            border: none;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
        }

        .staff-time-card__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.78rem;
            letter-spacing: 0.01em;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.05);
            color: #344455;
            background: #f8f8f8;
        }

        .chip-break {
            background: #fff8f0;
            color: #b15b10;
        }

        .chip-tip {
            background: #f2f6ff;
            color: #1a4f7a;
        }

        .break-control {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 12px;
            background: #f8f9fb;
            border: 1px solid #e6ebf1;
        }

        .break-control .break-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .break-countdown {
            font-weight: 800;
            color: #0f5c3f;
            font-size: 1rem;
        }

        .break-status {
            font-size: 0.8rem;
            color: #6c757d;
        }

        @media (max-width: 991px) {
            .staff-time-card {
                width: 100%;
            }

            #header.admin-header {
                padding: 8px 10px;
            }

            #header > .container-fluid {
                flex-wrap: nowrap;
                align-items: center;
                gap: 8px;
            }

            .header-actions {
                width: auto;
                margin-top: 0;
                flex-direction: row;
                flex-wrap: nowrap;
                align-items: center;
                justify-content: flex-start;
                gap: 8px;
                white-space: nowrap;
            }

            .datetime-chip {
                padding: 6px 10px;
                font-size: 0.75rem;
            }

            .datetime-chip__icon {
                width: 22px;
                height: 22px;
                font-size: 0.75rem;
            }

            .datetime-chip .live-clock {
                font-size: 0.9rem;
            }

            .datetime-chip .live-date {
                font-size: 0.7rem;
            }

            .header-calendar-link {
                padding: 6px 8px;
                border-radius: 12px;
            }

            .header-calendar-link__icon {
                width: 28px;
                height: 28px;
                font-size: 0.9rem;
            }

            .header-calendar-link__label {
                display: none;
            }

            .header-actions .nav-link {
                font-size: 0.9rem;
                padding: 0;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .header-actions img.rounded-circle {
                width: 28px;
                height: 28px;
            }

            .header-actions .badge {
                font-size: 0.75rem;
            }
        }

        #column-left {
            padding-top: 105px;
        }
    </style>
    <title>@yield('title')</title>
    @yield('styles')
</head>
<body>
    <div id="wrapper">
        <header id="header" class="navbar navbar-expand-lg navbar-light admin-header position-relative">
            <div class="container-fluid p-0">
                <div id="header-logo" class="pe-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="position-relative">
                            <img
                                id="header-logo-img"
                                src="{{ asset('assets/images/icon.png') }}"
                                alt="3K Fitness logo"
                                class="img-fluid"
                            />
                        </div>
                        <div class="d-none d-md-flex flex-column">
                            <span class="logo-fallback-text">3K FITNESS</span>
                            <span class="logo-tagline">Stronger every session</span>
                        </div>
                    </div>
                </div>
                
                <a href="#" id="button-menu"><i class="fa-solid fa-bars"></i></a>
                <a href="#" id="button-menu-close"><i class="fa-solid fa-xmark"></i></a>
                <a
                    href="{{ route('admin.gym-management.schedules') }}"
                    class="header-calendar-link"
                    aria-label="View calendar"
                    title="View calendar"
                >
                    <span class="header-calendar-link__icon">
                        <i class="fa-regular fa-calendar"></i>
                    </span>
                    <span class="header-calendar-link__label">View Calendar</span>
                </a>

                <div class="d-flex align-items-center ms-auto flex-wrap flex-lg-nowrap header-actions">
                    <div class="datetime-chip mt-2 mt-lg-0 me-lg-2">
                        <span class="datetime-chip__icon">
                            <i class="fa-regular fa-clock"></i>
                        </span>
                        <div class="datetime-chip__meta">
                            <span class="live-clock" data-format="with-seconds">--:-- --</span>
                            <span class="live-date">---</span>
                        </div>
                    </div>
                    @if(auth()->guard('admin')->user()->role_id == 2)
                        @php
                            $currentUserId = auth()->guard('admin')->user()->id;
                            $today = now()->toDateString();
                            $attendance = \App\Models\Attendance2::where('user_id', $currentUserId)
                                        ->where('is_archive', 0)
                                        ->whereDate('clockin_at', $today)
                                        ->orderByDesc('clockin_at')
                                        ->first();
                            $statusLabel = 'Not clocked in';
                            $statusClass = 'status-pill--pending';
                            $statusIcon = 'fa-regular fa-circle';
                            if ($attendance) {
                                if (is_null($attendance->clockout_at)) {
                                    $statusLabel = 'On the clock';
                                    $statusClass = 'status-pill--active';
                                    $statusIcon = 'fa-solid fa-bolt';
                                } else {
                                    $statusLabel = 'Shift finished';
                                    $statusClass = 'status-pill--done';
                                    $statusIcon = 'fa-solid fa-check';
                                }
                            }
                        @endphp
                    
                        <button type="button" class="btn time-action-trigger" data-bs-toggle="modal" data-bs-target="#timeActionsModal">
                            <i class="fa-regular fa-clock"></i>
                            Time actions
                        </button>
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
            </div>
        </header>
        <nav id="column-left">
            @php
                $gymRoutes = [
                    'admin.gym-management.index',
                    'admin.gym-management.schedules*',
                    'admin.gym-management.members*',
                    'admin.staff-account-management.memberships*',
                    'admin.staff-account-management.membership-payments*',
                ];
                $historyRoutes = [
                    'admin.history.class-enrollments*',
                    'admin.history.attendances*',
                    'admin.history.classes*',
                    'admin.history.payments*',
                    'admin.history.trainer-classes*',
                    'admin.history.memberships*',
                    'admin.history.reschedule-requests*',
                ];
                $staffAccountRoutes = [
                    'admin.staff-account-management.index',
                    'admin.staff-account-management.add',
                    'admin.staff-account-management.view',
                    'admin.staff-account-management.edit',
                    'admin.staff-account-management.store',
                    'admin.staff-account-management.update',
                    'admin.staff-account-management.delete',
                    'admin.staff-account-management.restore',
                    'admin.staff-account-management.print',
                    'admin.staff-account-management.attendances*',
                ];
                $adminManagementRoutes = ['admin.admins.*'];
                $trainerRoutes = ['admin.trainer-management.*'];
                $operationsRoutes = ['admin.banners.*', 'admin.trainer-banners.*', 'admin.logs.*'];
                $salesRoutes = ['admin.sales.index', 'admin.sales.report', 'admin.sales.reports'];
                $payrollRoutes = ['admin.payrolls.*'];
            @endphp
            <ul id="menu">
                <li>
                    <a href="{{ route('admin.dashboard.index') }}" class="{{ request()->routeIs('admin.dashboard.index') ? 'active' : '' }}">
                        <i class="fa-solid fa-gauge"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a class="collapsed {{ request()->routeIs($gymRoutes) ? 'active' : '' }}" 
                    data-bs-toggle="collapse" href="#gym-management-menu" 
                    role="button" 
                    aria-expanded="{{ request()->routeIs($gymRoutes) ? 'true' : 'false' }}" 
                    aria-controls="gym-management-menu">
                        <i class="fa-solid fa-dumbbell"></i> Gym Management
                    </a>
                
                    <ul id="gym-management-menu" class="collapse {{ request()->routeIs($gymRoutes) ? 'show' : '' }}">
                        <li>
                            <a href="{{ route('admin.gym-management.schedules') }}" 
                               class="{{ request()->routeIs('admin.gym-management.schedules*') ? 'active' : '' }}">
                               Classes
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.gym-management.members') }}" 
                               class="{{ request()->routeIs('admin.gym-management.members*') ? 'active' : '' }}">
                               Members Data
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.staff-account-management.memberships') }}" 
                               class="{{ request()->routeIs('admin.staff-account-management.memberships*') ? 'active' : '' }}">
                               Memberships
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.staff-account-management.membership-payments') }}" 
                               class="{{ request()->routeIs('admin.staff-account-management.membership-payments*') ? 'active' : '' }}">
                               Membership Payments
                            </a>
                        </li>
                    </ul>
                </li>                
                <li>
                    <a class="collapsed {{ request()->routeIs($historyRoutes) ? 'active' : '' }}"
                       data-bs-toggle="collapse"
                       href="#history-menu"
                       role="button"
                       aria-expanded="{{ request()->routeIs($historyRoutes) ? 'true' : 'false' }}"
                       aria-controls="history-menu">
                        <i class="fa-solid fa-clock-rotate-left"></i> History
                    </a>
                    <ul id="history-menu" class="collapse {{ request()->routeIs($historyRoutes) ? 'show' : '' }}">
                        <li>
                            <a href="{{ route('admin.history.class-enrollments') }}"
                               class="{{ request()->routeIs('admin.history.class-enrollments*') ? 'active' : '' }}">
                               Enrollment History
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.history.attendances') }}"
                               class="{{ request()->routeIs('admin.history.attendances*') ? 'active' : '' }}">
                               Attendances History
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.history.classes') }}"
                               class="{{ request()->routeIs('admin.history.classes*') ? 'active' : '' }}">
                               Classes History
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.history.payments') }}"
                               class="{{ request()->routeIs('admin.history.payments*') ? 'active' : '' }}">
                               Payments History
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.history.trainer-classes') }}"
                               class="{{ request()->routeIs('admin.history.trainer-classes*') ? 'active' : '' }}">
                               Trainer Classes History
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.history.reschedule-requests') }}"
                               class="{{ request()->routeIs('admin.history.reschedule-requests*') ? 'active' : '' }}">
                               Reschedule Requests
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.history.memberships') }}"
                               class="{{ request()->routeIs('admin.history.memberships*') ? 'active' : '' }}">
                               Membership History
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a class="collapsed {{ request()->routeIs($staffAccountRoutes) ? 'active' : '' }}" 
                        data-bs-toggle="collapse" href="#staff-account-management-menu" 
                        role="button" aria-expanded="{{ request()->routeIs($staffAccountRoutes) ? 'true' : 'false' }}" 
                        aria-controls="staff-account-management-menu">
                        <i class="fa-solid fa-users"></i> Staff Account Management
                    </a>
                    <ul id="staff-account-management-menu" class="collapse {{ request()->routeIs($staffAccountRoutes) ? 'show' : '' }}">
                        @if(auth()->guard('admin')->user()->role_id != 2)
                            <li>
                                <a href="{{ route('admin.staff-account-management.index') }}" 
                                   class="{{ request()->routeIs([
                                        'admin.staff-account-management.index',
                                        'admin.staff-account-management.add',
                                        'admin.staff-account-management.view',
                                        'admin.staff-account-management.edit',
                                        'admin.staff-account-management.store',
                                        'admin.staff-account-management.update',
                                        'admin.staff-account-management.delete',
                                        'admin.staff-account-management.restore',
                                        'admin.staff-account-management.print',
                                    ]) ? 'active' : '' }}">
                                   Overview
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ route('admin.staff-account-management.attendances') }}" 
                               class="{{ request()->routeIs('admin.staff-account-management.attendances*') ? 'active' : '' }}">
                               Attendances
                            </a>
                        </li>
                    </ul>
                </li>
                @if(auth()->guard('admin')->user()->role_id == 4)
                    <li>
                        <a class="collapsed {{ request()->routeIs($adminManagementRoutes) ? 'active' : '' }}"
                           data-bs-toggle="collapse"
                           href="#admin-management-menu"
                           role="button"
                           aria-expanded="{{ request()->routeIs($adminManagementRoutes) ? 'true' : 'false' }}"
                           aria-controls="admin-management-menu">
                            <i class="fa-solid fa-user-shield"></i> Admin Management
                        </a>
                        <ul id="admin-management-menu" class="collapse {{ request()->routeIs($adminManagementRoutes) ? 'show' : '' }}">
                            <li>
                                <a href="{{ route('admin.admins.index') }}" class="{{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                                    Admins
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
                <li>
                    <a class="collapsed {{ request()->routeIs($trainerRoutes) ? 'active' : '' }}" 
                    data-bs-toggle="collapse" 
                    href="#trainer-management-menu" 
                    role="button" 
                    aria-expanded="{{ request()->routeIs($trainerRoutes) ? 'true' : 'false' }}" 
                    aria-controls="trainer-management-menu">
                        <i class="fa-solid fa-dumbbell"></i> Trainer Management
                    </a>
                    <ul id="trainer-management-menu" class="collapse {{ request()->routeIs($trainerRoutes) ? 'show' : '' }}">
                        <li><a href="{{ route('admin.trainer-management.index') }}" class="{{ request()->routeIs('admin.trainer-management.*') ? 'active' : '' }}">Trainers</a></li>
                        {{-- <li><a href="{{ route('admin.trainer-management.add') }}" class="{{ Request::route()->getName() === 'admin.trainer-management.add' ? 'active' : '' }}">Add Trainer</a></li>
                        <li><a href="#" class="{{ Request::route()->getName() === 'admin.trainer-management.edit' ? 'active' : '' }}">Edit Trainer</a></li>
                        <li><a href="#" class="{{ Request::route()->getName() === 'admin.trainer-management.view' ? 'active' : '' }}">View Trainer</a></li> --}}
                    </ul>
                </li>
                @if(auth()->guard('admin')->user()->role_id != 2)
                    <li>
                        <a class="collapsed {{ request()->routeIs($operationsRoutes) ? 'active' : '' }}" 
                            data-bs-toggle="collapse" 
                            href="#operations-menu" 
                            role="button" 
                            aria-expanded="{{ request()->routeIs($operationsRoutes) ? 'true' : 'false' }}" 
                            aria-controls="operations-menu">
                            <i class="fa-solid fa-cogs"></i> Operations
                        </a>
                        <ul id="operations-menu" class="collapse {{ request()->routeIs($operationsRoutes) ? 'show' : '' }}">
                            <li><a href="{{ route('admin.banners.index') }}" class="{{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">Member Banner</a></li>
                            <li><a href="{{ route('admin.trainer-banners.index') }}" class="{{ request()->routeIs('admin.trainer-banners.*') ? 'active' : '' }}">Trainers Banner</a></li>
                            <li><a href="{{ route('admin.logs.index') }}" class="{{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">Logs</a></li>
                        </ul>
                    </li>
                @endif
                @if(auth()->guard('admin')->user()->role_id != 2)
                    <li>
                        <a
                            class="collapsed {{ request()->routeIs($salesRoutes) ? 'active' : '' }}"
                            data-bs-toggle="collapse"
                            href="#sales-menu"
                            role="button"
                            aria-expanded="{{ request()->routeIs($salesRoutes) ? 'true' : 'false' }}"
                            aria-controls="sales-menu"
                        >
                            <i class="fa-solid fa-chart-line"></i> Sales Overview
                        </a>
                        <ul id="sales-menu" class="collapse {{ request()->routeIs($salesRoutes) ? 'show' : '' }}">
                            {{-- <li>
                                <a href="{{ route('admin.sales.index') }}" class="{{ request()->routeIs('admin.sales.index') ? 'active' : '' }}">
                                    Overview
                                </a>
                            </li> --}}
                            <li>
                                <a href="{{ route('admin.sales.report') }}" class="{{ request()->routeIs('admin.sales.report') ? 'active' : '' }}">
                                    Sales Report
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.sales.reports') }}" class="{{ request()->routeIs('admin.sales.reports') ? 'active' : '' }}">
                                    Profit Report
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
                @if(auth()->guard('admin')->user()->role_id != 2)
                    <li>
                        <a
                            class="collapsed {{ request()->routeIs($payrollRoutes) ? 'active' : '' }}"
                            data-bs-toggle="collapse"
                            href="#payroll-menu"
                            role="button"
                            aria-expanded="{{ request()->routeIs($payrollRoutes) ? 'true' : 'false' }}"
                            aria-controls="payroll-menu"
                    >
                        <i class="fa-solid fa-money-bill"></i> Payroll
                    </a>
                    <ul id="payroll-menu" class="collapse {{ request()->routeIs($payrollRoutes) ? 'show' : '' }}">
                        <li>
                            <a href="{{ route('admin.payrolls.index') }}" class="{{ request()->routeIs('admin.payrolls.report') ? '' : (request()->routeIs('admin.payrolls.*') ? 'active' : '') }}">
                                Overview
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.payrolls.report') }}" class="{{ request()->routeIs('admin.payrolls.report') ? 'active' : '' }}">
                                Payroll Report
                            </a>
                        </li>
                    </ul>
                </li>
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
        <footer>Copyright. &copy; 2025 All Rights Reserved.</footer>
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

<div class="modal fade" id="printScopeModal" tabindex="-1" aria-labelledby="printScopeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printScopeModalLabel">Choose what to print</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Use the current filters and pick whether to print only the records on this page or every page.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-secondary" data-print-scope="current">Current page</button>
                <button type="button" class="btn btn-danger" data-print-scope="all">All pages</button>
            </div>
        </div>
    </div>
</div>

@if(auth()->guard('admin')->user()->role_id == 2)
<div class="modal fade" id="timeActionsModal" tabindex="-1" aria-labelledby="timeActionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timeActionsModalLabel">Your shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @php
                    $currentUserId = auth()->guard('admin')->user()->id;
                    $today = now()->toDateString();
                    $attendance = \App\Models\Attendance2::where('user_id', $currentUserId)
                                ->where('is_archive', 0)
                                ->whereDate('clockin_at', $today)
                                ->orderByDesc('clockin_at')
                                ->first();
                    $statusLabel = 'Not clocked in';
                    $statusClass = 'status-pill--pending';
                    $statusIcon = 'fa-regular fa-circle';
                    if ($attendance) {
                        if (is_null($attendance->clockout_at)) {
                            $statusLabel = 'On the clock';
                            $statusClass = '';
                            $statusIcon = 'fa-solid fa-bolt';
                        } else {
                            $statusLabel = 'Shift finished';
                            $statusClass = 'status-pill--done';
                            $statusIcon = 'fa-solid fa-check';
                        }
                    }
                @endphp
                <div class="staff-time-card">
                    <div class="staff-time-card__top">
                        <div class="staff-time-card__clock">
                            <span class="staff-time-card__clock-icon">
                                <i class="fa-regular fa-clock"></i>
                            </span>
                            <div>
                                <div class="staff-time-card__time live-clock" data-format="with-seconds">--:-- --</div>
                                <div class="staff-time-card__meta">
                                    Today, {{ now()->format('M d, Y') }} - Break time: 1 hour
                                </div>
                            </div>
                        </div>
                        <span class="status-pill {{ $statusClass }}">
                            <i class="{{ $statusIcon }}"></i> {{ $statusLabel }}
                        </span>
                    </div>
                    <div class="staff-time-card__actions">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            @if(!$attendance)
                                <button type="button" class="btn btn-sm btn-success time-action-btn" data-bs-toggle="modal" data-bs-target="#confirmationModal" data-action="{{ route('admin.payrolls.clockin') }}" data-message="Are you sure you want to Clock In?">
                                    <i class="fa-solid fa-play me-1"></i> Clock In
                                </button>
                            @else
                                @if(is_null($attendance->clockout_at))
                                    <button type="button" class="btn btn-sm btn-danger time-action-btn" data-bs-toggle="modal" data-bs-target="#confirmationModal" data-action="{{ route('admin.payrolls.clockout') }}" data-message="Are you sure you want to Clock Out?">
                                        <i class="fa-solid fa-stopwatch me-1"></i> Clock Out
                                    </button>
                                @else
                                    <span class="text-success fw-bold">Already Clocked Out</span>
                                @endif
                            @endif
                            <span class="chip chip-break">
                                <i class="fa-solid fa-mug-hot"></i>
                                Break time - 1 hour
                            </span>
                        </div>
                        <div class="staff-time-card__chips">
                            <span class="chip chip-tip">
                                <i class="fa-solid fa-star"></i>
                                Keep your shift clean: clock in, enjoy your 1-hour break, and clock out.
                            </span>
                        </div>
                        <div class="break-control mt-2">
                            <div class="break-meta">
                                <div class="break-countdown" id="breakCountdown">01:00:00</div>
                                <div class="break-status" id="breakStatus">Ready for a 1-hour break</div>
                            </div>
                            <button type="button" class="btn btn-outline-success btn-sm" id="breakToggleBtn">
                                Start Break
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

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

        document.addEventListener('DOMContentLoaded', function () {
            var liveClocks = document.querySelectorAll('.live-clock');
            var liveDates = document.querySelectorAll('.live-date');
            if (!liveClocks.length && !liveDates.length) {
                return;
            }

            var updateClock = function () {
                var now = new Date();

                liveClocks.forEach(function (el) {
                    var format = el.getAttribute('data-format') || 'with-seconds';
                    var options = { hour: '2-digit', minute: '2-digit', hour12: true };
                    if (format === 'with-seconds') {
                        options.second = '2-digit';
                    }
                    el.textContent = now.toLocaleTimeString('en-US', options);
                });

                liveDates.forEach(function (el) {
                    var dateOptions = { weekday: 'short', month: 'short', day: 'numeric' };
                    el.textContent = now.toLocaleDateString('en-US', dateOptions);
                });
            };

            updateClock();
            setInterval(updateClock, 1000);
        });

        document.addEventListener('DOMContentLoaded', function () {
            var countdownEl = document.getElementById('breakCountdown');
            var statusEl = document.getElementById('breakStatus');
            var toggleBtn = document.getElementById('breakToggleBtn');

            if (!countdownEl || !statusEl || !toggleBtn) {
                return;
            }

            var BREAK_DURATION_MS = 60 * 60 * 1000; // 1 hour
            var STATE_KEY = 'staffBreakState';
            var END_KEY = 'staffBreakEnd';
            var intervalId = null;

            var readState = function () {
                var state = localStorage.getItem(STATE_KEY) || 'idle';
                var endAt = parseInt(localStorage.getItem(END_KEY), 10);
                return { state: state, endAt: isNaN(endAt) ? null : endAt };
            };

            var saveState = function (state, endAt) {
                localStorage.setItem(STATE_KEY, state);
                if (endAt) {
                    localStorage.setItem(END_KEY, endAt.toString());
                } else {
                    localStorage.removeItem(END_KEY);
                }
            };

            var formatTime = function (msRemaining) {
                var totalSeconds = Math.max(0, Math.floor(msRemaining / 1000));
                var hours = Math.floor(totalSeconds / 3600);
                var minutes = Math.floor((totalSeconds % 3600) / 60);
                var seconds = totalSeconds % 60;
                return [
                    hours.toString().padStart(2, '0'),
                    minutes.toString().padStart(2, '0'),
                    seconds.toString().padStart(2, '0'),
                ].join(':');
            };

            var updateUI = function () {
                var current = readState();
                var now = Date.now();

                if (current.state === 'running' && current.endAt) {
                    var msRemaining = current.endAt - now;
                    if (msRemaining <= 0) {
                        saveState('done', null);
                        statusEl.textContent = 'Break finished — clock back in';
                        countdownEl.textContent = '00:00:00';
                        toggleBtn.textContent = 'Start New Break';
                        toggleBtn.classList.remove('btn-outline-danger');
                        toggleBtn.classList.add('btn-outline-success');
                        clearInterval(intervalId);
                        intervalId = null;
                        return;
                    }

                    countdownEl.textContent = formatTime(msRemaining);
                    statusEl.textContent = 'On break…';
                    toggleBtn.textContent = 'End Break';
                    toggleBtn.classList.remove('btn-outline-success');
                    toggleBtn.classList.add('btn-outline-danger');
                    return;
                }

                if (current.state === 'done') {
                    statusEl.textContent = 'Break finished — clock back in';
                    countdownEl.textContent = '00:00:00';
                    toggleBtn.textContent = 'Start New Break';
                    toggleBtn.classList.remove('btn-outline-danger');
                    toggleBtn.classList.add('btn-outline-success');
                    clearInterval(intervalId);
                    intervalId = null;
                    return;
                }

                // idle state
                statusEl.textContent = 'Ready for a 1-hour break';
                countdownEl.textContent = formatTime(BREAK_DURATION_MS);
                toggleBtn.textContent = 'Start Break';
                toggleBtn.classList.remove('btn-outline-danger');
                toggleBtn.classList.add('btn-outline-success');
                clearInterval(intervalId);
                intervalId = null;
            };

            var startTicker = function () {
                if (intervalId) {
                    clearInterval(intervalId);
                }
                intervalId = setInterval(updateUI, 1000);
                updateUI();
            };

            var startBreak = function () {
                var endAt = Date.now() + BREAK_DURATION_MS;
                saveState('running', endAt);
                startTicker();
            };

            var endBreak = function () {
                saveState('done', null);
                updateUI();
            };

            toggleBtn.addEventListener('click', function () {
                var current = readState();
                if (current.state === 'running') {
                    endBreak();
                } else {
                    startBreak();
                }
            });

            // Initialize UI and ticker if needed
            var initial = readState();
            if (initial.state === 'running') {
                startTicker();
            } else {
                updateUI();
            }
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
