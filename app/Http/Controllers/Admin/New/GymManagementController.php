<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GymManagementController extends Controller
{
    public function index()
    {
        return view('admin.gymmanagement.index');
    }
}
