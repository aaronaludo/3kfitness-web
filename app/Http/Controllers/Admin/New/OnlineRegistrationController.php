<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OnlineRegistrationController extends Controller
{
    public function index()
    {
        return view('admin.onlineregistrations.index');
    }
}
