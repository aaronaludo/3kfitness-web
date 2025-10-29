<?php

use Illuminate\Support\Facades\Route;

// use App\Http\Controllers\Passenger\Mobile\MobileAccountController;
// use App\Http\Controllers\Passenger\Mobile\MobileAuthController;
// use App\Http\Controllers\Passenger\Mobile\MobileQrCodeController;
// use App\Http\Controllers\Passenger\Mobile\MobileRideHistoryController;
// use App\Http\Controllers\Passenger\Mobile\MobileEmergencyController;
// use App\Http\Controllers\Passenger\Mobile\MobileConnectController;

// use App\Http\Controllers\Driver\Mobile\MobileDriverAccountController;
// use App\Http\Controllers\Driver\Mobile\MobileDriverAuthController;
// use App\Http\Controllers\Driver\Mobile\MobileDriverQrCodeController;
// use App\Http\Controllers\Driver\Mobile\MobileDriverRideHistoryController;

// Route::prefix('passengers')->group(function () {
//     Route::get('/test', [MobileAuthController::class, 'test'])->name('passengers.test');
//     Route::post('/login', [MobileAuthController::class, 'login'])->name('passengers.login');
//     Route::post('/register', [MobileAuthController::class, 'register'])->name('passengers.register');
// });

// Route::prefix('drivers')->group(function () {
//     Route::get('/test', [MobileDriverAuthController::class, 'test'])->name('drivers.test');
//     Route::post('/login', [MobileDriverAuthController::class, 'login'])->name('drivers.login');
//     Route::post('/register', [MobileDriverAuthController::class, 'register'])->name('drivers.register');
//     Route::post('/driver-information/{id}', [MobileDriverAuthController::class, 'driverInformation'])->name('drivers.driver-information');
// });

// Route::group(['middleware' => ['auth:sanctum']], function () {
//     Route::prefix('passengers')->group(function () {
//         Route::get('/index', [MobileAuthController::class, 'index'])->name('passengers.index');
//         Route::get('/qr-code/{qr_code}', [MobileQrCodeController::class, 'index'])->name('passengers.qr-code.index');

//         Route::get('/ride-histories', [MobileRideHistoryController::class, 'index'])->name('passengers.ride-histories.index');
//         Route::post('/ride-histories/status', [MobileRideHistoryController::class, 'status'])->name('passengers.ride-histories.status');
//         Route::get('/ride-histories/{id}', [MobileRideHistoryController::class, 'view'])->name('passengers.ride-histories.view');

//         Route::get('/emergencies', [MobileEmergencyController::class, 'index'])->name('passengers.emergencies.index');
//         Route::get('/emergencies/call/{id}', [MobileEmergencyController::class, 'call'])->name('passengers.emergencies.call');
//         Route::post('/emergencies/add', [MobileEmergencyController::class, 'add'])->name('passengers.emergencies.add');
//         Route::delete('/emergencies/{id}', [MobileEmergencyController::class, 'delete'])->name('passengers.emergencies.delete');

//         Route::get('/connects', [MobileConnectController::class, 'index'])->name('passengers.connects.index');
//         Route::post('/connects/add', [MobileConnectController::class, 'add'])->name('passengers.connects.add');
//         Route::delete('/connects/{id}', [MobileConnectController::class, 'delete'])->name('passengers.connects.delete');

//         Route::post('/edit-profile', [MobileAccountController::class, 'editProfile'])->name('passengers.edit-profile');
//         Route::post('/change-password', [MobileAccountController::class, 'changePassword'])->name('passengers.change-password');

//         Route::get('/logout', [MobileAuthController::class, 'logout'])->name('passengers.logout');
//     });
//     Route::prefix('drivers')->group(function () {
//         Route::get('/index', [MobileDriverAuthController::class, 'index'])->name('drivers.index');
//         Route::get('/qr-code/{id}', [MobileDriverQrCodeController::class, 'index'])->name('drivers.qr-code.index');

//         Route::get('/ride-histories', [MobileDriverRideHistoryController::class, 'index'])->name('drivers.ride-histories.index');
//         Route::get('/ride-histories/{id}', [MobileDriverRideHistoryController::class, 'view'])->name('drivers.ride-histories.view');

//         Route::post('/edit-profile', [MobileDriverAccountController::class, 'editProfile'])->name('drivers.edit-profile');
//         Route::post('/change-password', [MobileDriverAccountController::class, 'changePassword'])->name('drivers.change-password');

//         Route::get('/logout', [MobileDriverAuthController::class, 'logout'])->name('drivers.logout');
//     });
// });


use App\Http\Controllers\Member\MemberAuthController;
use App\Http\Controllers\Member\MemberAccountController;
use App\Http\Controllers\Member\MemberMembershipController;
use App\Http\Controllers\Member\MemberClassController;

use App\Http\Controllers\Trainer\TrainerAuthController;
use App\Http\Controllers\Trainer\TrainerAccountController;
use App\Http\Controllers\Trainer\TrainerClassController;

use App\Http\Controllers\Mobile\BannerController;
use App\Http\Controllers\Mobile\AboutController;
use App\Http\Controllers\Mobile\MotivationalVideoController;
use App\Http\Controllers\Mobile\WorkoutCategoryController;
use App\Http\Controllers\Mobile\DietCategoryController;
use App\Http\Controllers\Mobile\AttendanceController;

Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
Route::get('/abouts', [AboutController::class, 'index'])->name('abouts.index');
Route::get('/motivational-videos', [MotivationalVideoController::class, 'index'])->name('motivational-videos.index');
Route::get('/workout-categories', [WorkoutCategoryController::class, 'index'])->name('workout-categories.index');
Route::get('/diet-categories', [DietCategoryController::class, 'index'])->name('diet-categories.index');

Route::prefix('members')->group(function () {
    Route::get('/test', [MemberAuthController::class, 'test'])->name('members.test');
    Route::post('/login', [MemberAuthController::class, 'login'])->name('members.login');
    Route::post('/register', [MemberAuthController::class, 'register'])->name('members.register');
});

Route::prefix('trainers')->group(function () {
    Route::get('/test', [TrainerAuthController::class, 'test'])->name('trainers.test');
    Route::post('/login', [TrainerAuthController::class, 'login'])->name('trainers.login');
    Route::post('/register', [TrainerAuthController::class, 'register'])->name('trainers.register');
    
    Route::get('/available-classes', [TrainerClassController::class, 'availableclasses'])->name('trainers.available-classes');
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/attendance-histories', [AttendanceController::class, 'index'])->name('attendance-histories.index');
    
    Route::prefix('members')->group(function () {
        Route::get('/memberships', [MemberMembershipController::class, 'index'])->name('members.membership.index');
        Route::post('/memberships/checkout', [MemberMembershipController::class, 'checkout'])->name('members.membership.checkout');
        Route::get('/logout', [MemberAuthController::class, 'logout'])->name('members.logout');
        
        Route::get('/classes', [MemberClassController::class, 'index'])->name('members.classes');
        Route::post('/join-class', [MemberClassController::class, 'joinclass'])->name('members.join-class');
        Route::post('/leave-class', [MemberClassController::class, 'leaveclass'])->name('members.leave-class');

        Route::post('/edit-profile', [MemberAccountController::class, 'editProfile'])->name('members.edit-profile');
        Route::post('/change-password', [MemberAccountController::class, 'changePassword'])->name('members.change-password');
    });

    Route::prefix('trainers')->group(function () {
        Route::get('/logout', [TrainerAuthController::class, 'logout'])->name('trainers.logout');
        
        Route::get('/classes', [TrainerClassController::class, 'index'])->name('trainers.classes');
        Route::get('/my-classes', [TrainerClassController::class, 'myclasses'])->name('trainers.my-classes');
        Route::get('/my-classes-by-admin', [TrainerClassController::class, 'myclassesbyadmin'])->name('trainers.my-classes-by-admin');

        Route::post('/apply-available-class', [TrainerClassController::class, 'applyavailableclass'])->name('trainers.apply-available-class');
        Route::post('/trainer-approve-class', [TrainerClassController::class, 'trainerapproveclass'])->name('trainers.trainer-approve-class');
        Route::post('/trainer-reject-class', [TrainerClassController::class, 'trainerrejectclass'])->name('trainers.trainer-reject-class');
         
        Route::post('/create-diet-categories', [DietCategoryController::class, 'create'])->name('diet-categories.create');
        Route::delete('/delete-diet-categories', [DietCategoryController::class, 'delete'])->name('diet-categories.delete');
        
        Route::post('/create-workout-categories', [WorkoutCategoryController::class, 'create'])->name('workout-categories.create');
        Route::delete('/delete-workout-categories', [WorkoutCategoryController::class, 'delete'])->name('workout-categories.delete');
        
        Route::post('/edit-profile', [TrainerAccountController::class, 'editProfile'])->name('trainers.edit-profile');
        Route::post('/change-password', [TrainerAccountController::class, 'changePassword'])->name('trainers.change-password');
    });
});