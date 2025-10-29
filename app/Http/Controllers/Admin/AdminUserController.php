<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class AdminUserController extends Controller
{
    public function index(){
        $users = User::whereIn('role_id', [2, 3])->get();

        return view('admin.users', compact('users'));
    }

    public function view($id){
        $user = User::whereIn('role_id', [2, 3])->find($id);

        return view('admin.users-view', compact('user'));
    }

    public function search(Request $request){
        $search = $request->search;
        $users = User::whereIn('role_id', [2, 3])->where('email', 'like', '%' . $search . '%')->get();

        return view('admin.users', compact('users'));
    }

    public function verify(Request $request, $id){
        $user = User::find($id);
        
        $user->status_id = $request->status_id;
        $user->save();

        return view('admin.users-view', compact('user'));
    }
}
