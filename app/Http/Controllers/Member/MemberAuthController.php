<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log as Logger;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Log;
use App\Models\UserMembership;
use App\Mail\MemberVerificationCode;

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
                $hasActiveMembership = UserMembership::where('user_id', $user->id)
                    ->where('isapproved', 1)
                    ->where(function ($query) {
                        $query->whereNull('expiration_at')
                              ->orWhere('expiration_at', '>', now());
                    })
                    ->where('is_archive', 0)
                    ->exists();

                if (! $hasActiveMembership) {
                    Auth::logout();

                    return response()->json([
                        'message' => 'You currently do not have an active membership. Please visit the gym to activate your account again.'
                    ], 403);
                }

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

    public function sendEmailVerificationCode(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role_id !== 3) {
            return response()->json(['message' => 'Member account only'], 401);
        }

        if ($user->is_email_verified) {
            return response()->json(['message' => 'Email already verified', 'user' => $user]);
        }

        $code = Str::upper(Str::random(10));
        $user->email_verification_code = $code;
        $user->is_email_verified = 0;
        $user->save();

        try {
            Mail::to($user->email)->send(new MemberVerificationCode($code));
            Logger::info('Member verification email sent.', ['user_id' => $user->id]);
        } catch (\Throwable $e) {
            Logger::error('Failed to send member verification email.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Verification code generated but email failed to send.',
                'error' => $e->getMessage(),
                'user' => $user,
            ], 500);
        }

        return response()->json(['message' => 'Verification code sent', 'user' => $user]);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $user = Auth::user();

        if (!$user || $user->role_id !== 3) {
            return response()->json(['message' => 'Member account only'], 401);
        }

        if ($user->is_email_verified) {
            return response()->json(['message' => 'Email already verified']);
        }

        if (strcasecmp((string) $user->email_verification_code, (string) $request->code) !== 0) {
            return response()->json(['message' => 'Invalid verification code'], 422);
        }

        $user->is_email_verified = 1;
        $user->email_verification_code = null;
        $user->save();

        return response()->json(['message' => 'Email verified successfully', 'user' => $user]);
    }
}
