<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Log;

class AdminAuthController extends Controller
{
    public function index(){
        if (Auth::guard('admin')->check()) {
            return redirect('/admin/dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'role_id'  => 'required|in:1,2,4',
        ]);

        $credentials = $request->only('email', 'password', 'role_id');

        if (Auth::guard('admin')->attempt($credentials)) {
            $user = Auth::guard('admin')->user();

            if ($user->role_id == $request->role_id) {
                $roleName = match ($user->role_id) {
                    1 => 'Admin',
                    2 => 'Staff',
                    4 => 'Super Admin',
                    default => 'Unknown'
                };

                $log = new Log;
                $log->message   = $user->first_name . " " . $user->last_name . " successfully logged into the admin panel.";
                $log->role_name = $roleName;
                $log->save();

                return redirect()->intended('/admin/dashboard');
            }

            Auth::guard('admin')->logout();
            return redirect()->route('login')->with('error', 'Selected role does not match your account.');
        }

        return redirect()->route('login')->with('error', 'Invalid credentials');
    }

    public function logout(){
        $user = Auth::guard('admin')->user();
        
        $log = new Log;
        $log->message = $user->first_name . " " . $user->last_name . " has logged out of the admin panel successfully.";
        $log->role_name = 'Admin';
        $log->save();
        
        Auth::guard('admin')->logout();
        return redirect('/login');
    }
}
