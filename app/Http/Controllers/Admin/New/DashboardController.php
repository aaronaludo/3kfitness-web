<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Feedback;

use App\Models\Membership;
use App\Models\Schedule;
use App\Models\UserMembership;
use App\Models\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $gym_members_count = User::where('role_id', 3)->count();
        $staffs_count = User::where('role_id', 2)->count();
        $feedbacks_count = Feedback::count();
        $memberships_count = Membership::count();
        $classes_count = Schedule::count();
        $user_membership_count = UserMembership::where('isapproved', 0)->count();
        
        $gym_members = User::where('role_id', 3)->limit(10)->get();
        $logs = Log::orderBy('id', 'desc')->limit(10)->get();
        
        return view('admin.dashboard.index', compact('gym_members_count', 'staffs_count', 'feedbacks_count', 'gym_members', 'memberships_count', 'classes_count', 'user_membership_count', 'logs'));
    }
}
