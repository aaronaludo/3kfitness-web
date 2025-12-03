<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class TrainerAccountController extends Controller
{
    public function editProfile(Request $request){
        $user = User::find(auth()->user()->id);

        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'address' => 'required|string',
            'phone_number' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($user->role_id != 5) {
            return response()->json(['message' => 'Trainer account only'], 401);
        }

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->phone_number = $request->phone_number;
        $user->address = $request->address;
        $user->email = $request->email;

        if ($request->hasFile('profile_picture')) {
            $destinationPath = public_path('uploads');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            if ($user->profile_picture) {
                $currentPath = public_path($user->profile_picture);
                if (File::exists($currentPath)) {
                    File::delete($currentPath);
                }
            }

            $profilePicture = $request->file('profile_picture');
            $profilePictureUrlName = time() . '_' . uniqid('profile_') . '.' . $profilePicture->getClientOriginalExtension();
            $profilePicture->move($destinationPath, $profilePictureUrlName);
            $user->profile_picture = 'uploads/' . $profilePictureUrlName;
        }
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
    
        if ($user->role_id != 5) {
            return response()->json(['message' => 'Trainer account only'], 401);
        }

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Old password is incorrect'], 401);
        }
    
        $user->password = bcrypt($request->new_password);
        $user->save();
    
        return response()->json(['message' => 'Password changed successfully']);
    }
}
