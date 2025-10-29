<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MemberAccountController extends Controller
{
    public function editProfile(Request $request){
        $user = User::find(auth()->user()->id);

        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'address' => 'required|string',
            'phone_number' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        if ($user->role_id != 3) {
            return response()->json(['message' => 'Member account only'], 401);
        }

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->phone_number = $request->phone_number;
        $user->address = $request->address;
        $user->email = $request->email;
        $user->save();

        return response()->json(['user' => $user]);
    }

    public function changePassword(Request $request){
        $user = User::find(auth()->user()->id);

        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|different:old_password',
            'confirm_password' => 'required|string|same:new_password',
        ]);
    
        if ($user->role_id != 3) {
            return response()->json(['message' => 'Member account only'], 401);
        }

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Old password is incorrect'], 401);
        }
    
        $user->password = bcrypt($request->new_password);
        $user->save();
    
        return response()->json(['message' => 'Password changed successfully']);
    }
}
