<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RideHistory;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index(){
        $users = User::whereIn('role_id', [2, 3])->count();
        $admins = User::whereIn('role_id', [1, 4])->count();
        $histories = RideHistory::all()->count();
        $latestHistories = RideHistory::latest()->take(5)->get();

        return view('admin.dashboard', compact('users','admins','histories','latestHistories'));
    }
}
