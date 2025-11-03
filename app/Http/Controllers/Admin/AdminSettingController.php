<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function index(){
        $this->logAdminActivity('viewed the settings page.');

        return view('admin.settings');
    }
}
