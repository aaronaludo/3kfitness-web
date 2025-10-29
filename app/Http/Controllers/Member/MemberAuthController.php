<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Log;

class MemberAuthController extends Controller
{
    public function test(){
        return response()->json(['message' => 'member test']);
    }

    public function index(){
        $user = User::find(auth()->user()->id);

        if ($user->role_id != 3) {
            return response()->json(['message' => 'Member account only'], 401);
        }

        return response()->json(['message' => 'index']);
    }

    public function login(Request $request){
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->role_id === 3 && $user->status_id === 2) {
                $token = $user->createToken('member_fithub_token')->plainTextToken;

                $response = [
                    'token' => $token,
                    'user' => $user
                ];

                $log = new Log;
                $log->message = $user->first_name . " " . $user->last_name . " successfully logged into the mobile application.";
                $log->role_name = 'Member';
                $log->save();
                
                return response()->json(['response' => $response]);
            }else if($user->role_id === 3 && $user->status_id === 1){
                return response()->json(['message' => 'Your account is pending']);
            }else{
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();
            return response()->json(['message' => 'Member account only'], 401);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function register(Request $request){
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'address' => 'required|string',
            'phone_number' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed'],
        ]);

        $user = new User();
        $user->role_id = 3;
        $user->status_id = 2;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->address = $request->address;
        $user->phone_number = $request->phone_number;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        $token = $user->createToken('member_fithub_token')->plainTextToken;

        $response = [
            'token' => $token,
            'user' => $user
        ];

        return response()->json(['response' => $response]);
    }

    public function logout(Request $request){
        $user = Auth::user();

        if ($user->role_id === 3) {
            $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();
            return response()->json(['message' => 'Successfully logged out']);
        }

        $log = new Log;
        $log->message = $user->first_name . " " . $user->last_name . " has logged out of the mobile application successfully.";
        $log->role_name = 'Member';
        $log->save();
        
        return response()->json(['message' => 'Member account only'], 401);
    }
}
