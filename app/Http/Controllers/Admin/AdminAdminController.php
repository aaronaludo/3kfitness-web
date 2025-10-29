<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AdminAdminController extends Controller
{
    public function index(){
        $users = User::where('role_id', 1)->get();

        return view('admin.admins', compact('users'));
    }

    public function add(){
        return view('admin.admins-add');
    }

    public function view($id){
        $user = User::where('role_id', 1)->find($id);

        return view('admin.admins-view', compact('user'));
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.admins.add')
                ->withErrors($validator)
                ->withInput();
        }

        $users = new User;
        $users->role_id = 1;
        $users->status_id = 2;
        $users->first_name = $request->first_name;
        $users->last_name = $request->last_name;
        $users->address = $request->address;
        $users->phone_number = $request->phone_number;
        $users->email = $request->email;
        $users->password = $request->password;
        $users->save();

        return redirect()->route('admin.admins.add')->with('success', 'Admin created successfully');
    }
}
