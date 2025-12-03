<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class AdminAccountController extends Controller
{
    public function changePassword(){
        $this->logAdminActivity('viewed the change password page.');

        return view('admin.change-password');
    }
    
    public function editProfile(){
        $this->logAdminActivity('viewed the edit profile page.');

        return view('admin.edit-profile');
    }

    public function updateProfile(Request $request){
        $authUser = auth()->guard('admin')->user();
        $user = User::findOrFail($authUser->id);
        $isStaff = $user->role_id === 2;

        $rules = [
            'address' => 'required|string|max:255',
            'phone_number' => ['required', 'regex:/^\+639\d{9}$/'],
        ];

        if (!$isStaff) {
            $inputEmail = $request->input('email', '');
            $emailRules = ['required', 'email'];
            if (strcasecmp($inputEmail, $user->email) !== 0) {
                $emailRules[] = Rule::unique('users', 'email');
            }

            $rules = array_merge($rules, [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => $emailRules,
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'remove_profile_picture' => 'nullable|boolean',
            ]);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('admin.edit-profile')
                ->withErrors($validator)
                ->withInput();
        }

        $user->address = $request->address;
        $user->phone_number = $request->phone_number;

        if (!$isStaff) {
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;

            $destinationPath = public_path('uploads');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $removeRequested = $request->boolean('remove_profile_picture');

            $deleteExistingImage = function () use ($user) {
                if ($user->profile_picture) {
                    $currentPath = public_path($user->profile_picture);
                    if (File::exists($currentPath)) {
                        File::delete($currentPath);
                    }
                    $user->profile_picture = null;
                }
            };

            if ($request->hasFile('profile_picture')) {
                $deleteExistingImage();

                $profilePicture = $request->file('profile_picture');
                $profilePictureUrlName = time() . '_' . uniqid('profile_') . '.' . $profilePicture->getClientOriginalExtension();
                $profilePicture->move($destinationPath, $profilePictureUrlName);
                $user->profile_picture = 'uploads/' . $profilePictureUrlName;
            } elseif ($removeRequested) {
                $deleteExistingImage();
            }
        }

        $user->save();

        $this->logAdminActivity('updated their profile information.');

        return redirect('/admin/edit-profile')->with('success', 'Profile updated successfully');
    }
    
    public function updatePassword(Request $request){
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.change-password')
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::find(auth()->guard('admin')->user()->id);

        if (!Hash::check($request->old_password, $user->password)) {
            return redirect()->route('admin.change-password')->with('error', 'Incorrect old password');
        }
        
        $user->password = Hash::make($request->new_password);
        $user->save();

        $this->logAdminActivity('updated their account password.');

        return redirect('/admin/change-password')->with('success', 'Password changed successfully');
    }
}
