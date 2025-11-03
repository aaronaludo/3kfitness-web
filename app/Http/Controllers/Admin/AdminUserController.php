<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class AdminUserController extends Controller
{
    public function index(){
        $users = User::whereIn('role_id', [2, 3])->get();

        $this->logAdminActivity('viewed the user accounts list.');

        return view('admin.users', compact('users'));
    }

    public function view($id){
        $user = User::whereIn('role_id', [2, 3])->find($id);

        $this->logAdminActivity("viewed user account details for user ID {$id}.");

        return view('admin.users-view', compact('user'));
    }

    public function search(Request $request){
        $search = $request->search;
        $users = User::whereIn('role_id', [2, 3])->where('email', 'like', '%' . $search . '%')->get();

        $term = trim((string) $search);

        if ($term !== '') {
            $sanitizedTerm = str_replace('"', "'", $term);
            $this->logAdminActivity('searched for user accounts with email containing "' . $sanitizedTerm . '".');
        } else {
            $this->logAdminActivity('searched for user accounts without a specific query.');
        }

        return view('admin.users', compact('users'));
    }

    public function verify(Request $request, $id){
        $user = User::find($id);
        
        $user->status_id = $request->status_id;
        $user->save();

        $this->logAdminActivity("updated the status of user ID {$id} to status ID {$request->status_id}.");

        return view('admin.users-view', compact('user'));
    }
}
