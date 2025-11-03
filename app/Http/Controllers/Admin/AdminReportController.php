<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(){
        $this->logAdminActivity('viewed the reports page.');

        return view('admin.reports');
    }
}
