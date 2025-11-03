<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RideHistory;

class AdminRideHistoryController extends Controller
{
    public function index(){
        $histories = RideHistory::all();

        $this->logAdminActivity('viewed the ride histories list.');

        return view('admin.ride-histories', compact('histories'));
    }
    public function view($id){
        $history = RideHistory::find($id);

        $this->logAdminActivity("viewed ride history details for record ID {$id}.");

        return view('admin.ride-histories-view', compact('history'));
    }
}
