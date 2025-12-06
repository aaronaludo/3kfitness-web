<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Models\User;
use Illuminate\Validation\Rule;

class AdminAdminController extends Controller
{
    public function index(){
        $users = User::where('role_id', 1)->get();

        $this->logAdminActivity('viewed the admin accounts list.');

        return view('admin.admins', compact('users'));
    }

    public function add(){
        $this->logAdminActivity('opened the admin account creation form.');

        return view('admin.admins-add');
    }

    public function view($id){
        $user = User::where('role_id', 1)->find($id);

        $this->logAdminActivity("viewed admin account details for user ID {$id}.");

        return view('admin.admins-view', compact('user'));
    }

    public function edit($id){
        $user = User::where('role_id', 1)->findOrFail($id);

        $this->logAdminActivity("opened the admin account edit form for user ID {$id}.");

        return view('admin.admins-edit', compact('user'));
    }

    public function update(Request $request, $id){
        $user = User::where('role_id', 1)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'regex:/^\\+639\\d{9}$/'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->where(fn ($q) => $q->where('role_id', 1))
                    ->ignore($id),
            ],
            'password' => ['nullable', 'confirmed'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'remove_profile_picture' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.admins.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->address = $request->address;
        $user->phone_number = $request->phone_number;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = $request->password;
        }

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

        $user->save();

        $this->logAdminActivity("updated admin account details for user ID {$id}.");

        return redirect()->route('admin.admins.edit', $id)->with('success', 'Admin updated successfully');
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
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
        $users->created_by = $request->user()->first_name . " " .  $request->user()->last_name;
        $destinationPath = public_path('uploads');
        if (!File::isDirectory($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        if ($request->hasFile('profile_picture')) {
            $profilePicture = $request->file('profile_picture');
            $profilePictureUrlName = time() . '_' . uniqid('profile_') . '.' . $profilePicture->getClientOriginalExtension();
            $profilePicture->move($destinationPath, $profilePictureUrlName);
            $users->profile_picture = 'uploads/' . $profilePictureUrlName;
        }
        $users->save();

        $prefix = match ((int) $users->role_id) {
            1 => 'A',
            2 => 'S',
            3 => 'M',
            4 => 'SA',
            5 => 'T',
            default => '',
        };
        $users->user_code = $prefix . $users->id;
        $users->save();

        $this->logAdminActivity("created a new admin account for {$request->first_name} {$request->last_name}.");

        return redirect()->route('admin.admins.add')->with('success', 'Admin created successfully');
    }
}
