<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminAdminController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminRideHistoryController;

use App\Http\Controllers\Admin\New\DashboardController as Dashboard;
use App\Http\Controllers\Admin\New\FeedbackController as Feedback;
use App\Http\Controllers\Admin\New\GymMemberAttendanceController as GymMemberAttendance;
use App\Http\Controllers\Admin\New\OnlineRegistrationController as OnlineRegistration;
use App\Http\Controllers\Admin\New\ReportController as Report;
use App\Http\Controllers\Admin\New\StaffAccountManagementController as StaffAccountManagement;
use App\Http\Controllers\Admin\New\ScheduleController as Schedule;
use App\Http\Controllers\Admin\New\MemberDataController as MemberData;
use App\Http\Controllers\Admin\New\AttendanceController as Attendance;
use App\Http\Controllers\Admin\New\MembershipController as Membership;
use App\Http\Controllers\Admin\New\UserMembershipController as UserMembership;
use App\Http\Controllers\Admin\New\LogController as Log;

use App\Http\Controllers\Admin\New\BannerController as Banner;
use App\Http\Controllers\Admin\New\GoalController as Goal;
use App\Http\Controllers\Admin\New\PopularWorkoutController as PopularWorkout;
use App\Http\Controllers\Admin\New\MotivationalVideoController as MotivationalVideo;
use App\Http\Controllers\Admin\New\WorkoutCategoryController as WorkoutCategory;
use App\Http\Controllers\Admin\New\DietCategoryController as DietCategory;
use App\Http\Controllers\Admin\New\HelpController as Help;
use App\Http\Controllers\Admin\New\AboutController as About;
use App\Http\Controllers\Admin\New\PayrollController as Payroll;

use App\Http\Controllers\Admin\New\TrainerManagementController as TrainerManagement;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AdminAuthController::class, 'index'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.process.login');

Route::middleware(['auth:admin'])->group(function () {

    // use App\Http\Controllers\Admin\New\DashboardController as Dashboard;
    // use App\Http\Controllers\Admin\New\FeedbackController as Feedback;
    // use App\Http\Controllers\Admin\New\GymManagementController as GymManagement;
    // use App\Http\Controllers\Admin\New\GymMemberAttendanceController as GymMemberAttendance;
    // use App\Http\Controllers\Admin\New\OnlineRegistrationController as OnlineRegistration;
    // use App\Http\Controllers\Admin\New\ReportController as Report;
    // use App\Http\Controllers\Admin\New\StaffAccountManagementController as StaffAccountManagement;

    // Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard.index');

    Route::get('/admin/banners', [Banner::class, 'index'])->name('admin.banners.index');
    Route::post('/admin/banners', [Banner::class, 'update'])->name('admin.banners.update');
    
    Route::get('/admin/abouts', [About::class, 'index'])->name('admin.abouts.index');
    Route::post('/admin/abouts', [About::class, 'update'])->name('admin.abouts.update');
    
    Route::get('/admin/motivational-videos', [MotivationalVideo::class, 'index'])->name('admin.motivational-videos.index');
    Route::get('/admin/motivational-videos/create', [MotivationalVideo::class, 'create'])->name('admin.motivational-videos.create');
    Route::get('/admin/motivational-videos/{id}', [MotivationalVideo::class, 'view'])->name('admin.motivational-videos.view');
    Route::post('/admin/motivational-videos', [MotivationalVideo::class, 'store'])->name('admin.motivational-videos.store');
    Route::post('/admin/motivational-videos/print', [MotivationalVideo::class, 'print'])->name('admin.motivational-videos.print');
    Route::get('/admin/motivational-videos/{id}/edit', [MotivationalVideo::class, 'edit'])->name('admin.motivational-videos.edit');
    Route::put('/admin/motivational-videos/{id}', [MotivationalVideo::class, 'update'])->name('admin.motivational-videos.update');
    Route::delete('/admin/motivational-videos', [MotivationalVideo::class, 'delete'])->name('admin.motivational-videos.delete');
    
    Route::get('/admin/workout-categories', [WorkoutCategory::class, 'index'])->name('admin.workout-categories.index');
    Route::get('/admin/workout-categories/create', [WorkoutCategory::class, 'create'])->name('admin.workout-categories.create');
    Route::get('/admin/workout-categories/{id}', [WorkoutCategory::class, 'view'])->name('admin.workout-categories.view');
    Route::post('/admin/workout-categories', [WorkoutCategory::class, 'store'])->name('admin.workout-categories.store');
    Route::post('/admin/workout-categories/print', [WorkoutCategory::class, 'print'])->name('admin.workout-categories.print');
    Route::get('/admin/workout-categories/{id}/edit', [WorkoutCategory::class, 'edit'])->name('admin.workout-categories.edit');
    Route::put('/admin/workout-categories/{id}', [WorkoutCategory::class, 'update'])->name('admin.workout-categories.update');
    Route::delete('/admin/workout-categories', [WorkoutCategory::class, 'delete'])->name('admin.workout-categories.delete');
    
    Route::get('/admin/diet-categories', [DietCategory::class, 'index'])->name('admin.diet-categories.index');
    Route::get('/admin/diet-categories/create', [DietCategory::class, 'create'])->name('admin.diet-categories.create');
    Route::get('/admin/diet-categories/{id}', [DietCategory::class, 'view'])->name('admin.diet-categories.view');
    Route::post('/admin/diet-categories', [DietCategory::class, 'store'])->name('admin.diet-categories.store');
    Route::post('/admin/diet-categories/print', [DietCategory::class, 'print'])->name('admin.diet-categories.print');
    Route::get('/admin/diet-categories/{id}/edit', [DietCategory::class, 'edit'])->name('admin.diet-categories.edit');
    Route::put('/admin/diet-categories/{id}', [DietCategory::class, 'update'])->name('admin.diet-categories.update');
    Route::delete('/admin/diet-categories', [DietCategory::class, 'delete'])->name('admin.diet-categories.delete');
    
    Route::get('/admin/goals', [Goal::class, 'index'])->name('admin.goals.index');
    
    Route::get('/admin/popular-workouts', [PopularWorkout::class, 'index'])->name('admin.popular-workouts.index');
    Route::get('/admin/helps', [Help::class, 'index'])->name('admin.helps.index');

    Route::get('/admin/payrolls', [Payroll::class, 'index'])->name('admin.payrolls.index');
    Route::get('/admin/payrolls/{id}', [Payroll::class, 'view'])->name('admin.payrolls.view');
    Route::post('/admin/payrolls/clockin', [Payroll::class, 'clockin'])->name('admin.payrolls.clockin');
    Route::post('/admin/payrolls/clockout', [Payroll::class, 'clockout'])->name('admin.payrolls.clockout');
    
    Route::get('/admin/dashboard', [Dashboard::class, 'index'])->name('admin.dashboard.index');
    
    Route::get('/admin/feedbacks', [Feedback::class, 'index'])->name('admin.feedbacks.index');

    Route::get('/admin/gym-management', [GymManagement::class, 'index'])->name('admin.gym-management.index');

    Route::get('/admin/memberships', [Membership::class, 'index'])->name('admin.staff-account-management.memberships');
    Route::get('/admin/memberships/create', [Membership::class, 'create'])->name('admin.staff-account-management.memberships.create');
    Route::get('/admin/memberships/{id}', [Membership::class, 'view'])->name('admin.staff-account-management.memberships.view');
    Route::post('/admin/memberships', [Membership::class, 'store'])->name('admin.staff-account-management.memberships.store');
    Route::post('/admin/memberships/print', [Membership::class, 'print'])->name('admin.staff-account-management.memberships.print');
    Route::get('/admin/memberships/{id}/edit', [Membership::class, 'edit'])->name('admin.staff-account-management.memberships.edit');
    Route::put('/admin/memberships/{id}', [Membership::class, 'update'])->name('admin.staff-account-management.memberships.update');
    Route::delete('/admin/memberships', [Membership::class, 'delete'])->name('admin.staff-account-management.memberships.delete');

    Route::get('/admin/user-memberships', [UserMembership::class, 'index'])->name('admin.staff-account-management.user-memberships');
    Route::post('/admin/user-memberships/isapprove', [UserMembership::class, 'isapprove'])->name('admin.staff-account-management.user-memberships.isapprove');
    Route::post('/admin/user-memberships/print', [UserMembership::class, 'print'])->name('admin.staff-account-management.user-memberships.print');
    Route::get('/admin/user-memberships/{id}', [UserMembership::class, 'view'])->name('admin.staff-account-management.user-memberships.view');
    Route::delete('/admin/user-memberships', [UserMembership::class, 'delete'])->name('admin.staff-account-management.user-memberships.delete');
    Route::match(['put', 'post'], '/admin/user-memberships/restore', [UserMembership::class, 'restore'])->name('admin.staff-account-management.user-memberships.restore');

    Route::get('/admin/classes', [Schedule::class, 'index'])->name('admin.gym-management.schedules');
    Route::get('/admin/classes/all', [Schedule::class, 'all'])->name('admin.gym-management.schedules.all');
    Route::get('/admin/classes/create', [Schedule::class, 'create'])->name('admin.gym-management.schedules.create');
    Route::get('/admin/classes/{id}/users', [Schedule::class, 'users'])->name('admin.gym-management.schedules.users');
    Route::get('/admin/classes/{id}', [Schedule::class, 'view'])->name('admin.gym-management.schedules.view');
    Route::post('/admin/classes', [Schedule::class, 'store'])->name('admin.gym-management.schedules.store');
    Route::post('/admin/classes/print', [Schedule::class, 'print'])->name('admin.gym-management.schedules.print');
    Route::get('/admin/classes/{id}/edit', [Schedule::class, 'edit'])->name('admin.gym-management.schedules.edit');
    Route::put('/admin/classes/{id}', [Schedule::class, 'update'])->name('admin.gym-management.schedules.update');
    Route::delete('/admin/classes', [Schedule::class, 'delete'])->name('admin.gym-management.schedules.delete');
    Route::match(['put', 'post'], '/admin/classes/restore', [Schedule::class, 'restore'])->name('admin.gym-management.schedules.restore');
    Route::put('/admin/admin-acceptance-classes', [Schedule::class, 'adminacceptance'])->name('admin.gym-management.schedules.adminacceptance');
    Route::post('/admin/reject-message-classes', [Schedule::class, 'rejectmessage'])->name('admin.gym-management.schedules.rejectmessage');
    
    Route::get('/admin/members', [MemberData::class, 'index'])->name('admin.gym-management.members');
    Route::get('/admin/members/create', [MemberData::class, 'create'])->name('admin.gym-management.members.create');
    Route::get('/admin/members/{id}', [MemberData::class, 'view'])->name('admin.gym-management.members.view');
    Route::post('/admin/members', [MemberData::class, 'store'])->name('admin.gym-management.members.store');
    Route::post('/admin/members/print', [MemberData::class, 'print'])->name('admin.gym-management.members.print');
    Route::get('/admin/members/{id}/edit', [MemberData::class, 'edit'])->name('admin.gym-management.members.edit');
    Route::put('/admin/members/{id}', [MemberData::class, 'update'])->name('admin.gym-management.members.update');
    Route::delete('/admin/members', [MemberData::class, 'delete'])->name('admin.gym-management.members.delete');
 
    Route::get('/admin/online-registrations', [OnlineRegistration::class, 'index'])->name('admin.online-registrations.index');
    Route::get('/admin/reports', [Report::class, 'index'])->name('admin.reports.index');

    Route::get('/admin/staff-account-management/attendances', [Attendance::class, 'index'])->name('admin.staff-account-management.attendances');
    Route::get('/admin/staff-account-management/attendances/scanner', [Attendance::class, 'scanner'])->name('admin.staff-account-management.attendances.scanner');
    Route::post('/admin/staff-account-management/attendances/print', [Attendance::class, 'print'])->name('admin.staff-account-management.attendances.print');
    Route::post('/admin/staff-account-management/attendances/scanner', [Attendance::class, 'fetchScanner'])->name('admin.staff-account-management.attendances.scanner.fetch');
    Route::post('/admin/staff-account-management/attendances/scanner2', [Attendance::class, 'fetchScanner2'])->name('admin.staff-account-management.attendances.scanner2.fetch');
    
    Route::get('/admin/staff-account-management', [StaffAccountManagement::class, 'index'])->name('admin.staff-account-management.index');
    Route::get('/admin/staff-account-management/add', [StaffAccountManagement::class, 'add'])->name('admin.staff-account-management.add');
    Route::get('/admin/staff-account-management/{id}', [StaffAccountManagement::class, 'view'])->name('admin.staff-account-management.view');
    Route::post('/admin/staff-account-management/store', [StaffAccountManagement::class, 'store'])->name('admin.staff-account-management.store');
    Route::post('/admin/staff-account-management/print', [StaffAccountManagement::class, 'print'])->name('admin.staff-account-management.print');
    Route::get('/admin/staff-account-management/{id}/edit', [StaffAccountManagement::class, 'edit'])->name('admin.staff-account-management.edit');
    Route::put('/admin/staff-account-management/{id}', [StaffAccountManagement::class, 'update'])->name('admin.staff-account-management.update');
    Route::delete('/admin/staff-account-management', [StaffAccountManagement::class, 'delete'])->name('admin.staff-account-management.delete');
    
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/search', [AdminUserController::class, 'search'])->name('admin.users.search');
    Route::post('/admin/users/verify/{id}', [AdminUserController::class, 'verify'])->name('admin.users.verify');
    Route::get('/admin/users/{id}', [AdminUserController::class, 'view'])->name('admin.users.view');

    Route::get('/admin/admins', [AdminAdminController::class, 'index'])->name('admin.admins.index');
    Route::get('/admin/admins/add', [AdminAdminController::class, 'add'])->name('admin.admins.add');
    Route::get('/admin/admins/{id}', [AdminAdminController::class, 'view'])->name('admin.admins.view');
    Route::post('/admin/admins/store', [AdminAdminController::class, 'store'])->name('admin.admins.store');

    Route::get('/admin/ride-histories', [AdminRideHistoryController::class, 'index'])->name('admin.ride-histories.index');
    Route::get('/admin/ride-histories/{id}', [AdminRideHistoryController::class, 'view'])->name('admin.ride-histories.view');

    Route::get('/admin/logs', [Log::class, 'index'])->name('admin.logs.index');
    Route::post('/admin/logs/print', [Log::class, 'print'])->name('admin.logs.print');
    // Route::get('/admin/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');

    Route::get('/admin/trainer-management', [TrainerManagement::class, 'index'])->name('admin.trainer-management.index');
    Route::get('/admin/trainer-management/add', [TrainerManagement::class, 'add'])->name('admin.trainer-management.add');
    Route::post('/admin/trainer-management/print', [TrainerManagement::class, 'print'])->name('admin.trainer-management.print');
    Route::get('/admin/trainer-management/{id}', [TrainerManagement::class, 'view'])->name('admin.trainer-management.view');
    Route::get('/admin/trainer-management/{id}/edit', [TrainerManagement::class, 'edit'])->name('admin.trainer-management.edit');
    Route::post('/admin/trainer-management', [TrainerManagement::class, 'store'])->name('admin.trainer-management.store');
    Route::put('/admin/trainer-management/{id}', [TrainerManagement::class, 'update'])->name('admin.trainer-management.update');
    Route::delete('/admin/trainer-management', [TrainerManagement::class, 'delete'])->name('admin.trainer-management.delete');
    Route::match(['put', 'post'], '/admin/trainer-management/restore', [TrainerManagement::class, 'restore'])->name('admin.trainer-management.restore');

    Route::get('/admin/settings', [AdminSettingController::class, 'index'])->name('admin.settings.index');

    Route::get('/admin/change-password', [AdminAccountController::class, 'changePassword'])->name('admin.change-password');
    Route::get('/admin/edit-profile', [AdminAccountController::class, 'editProfile'])->name('admin.edit-profile');
    Route::post('/admin/update-profile', [AdminAccountController::class, 'updateProfile'])->name('admin.account.update-profile');
    Route::post('/admin/update-change-password', [AdminAccountController::class, 'updatePassword'])->name('admin.account.update_change_password');

    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});
