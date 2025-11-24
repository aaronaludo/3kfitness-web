<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log as Logger;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Log;
use App\Models\MembershipPayment;
use App\Models\Membership;
use App\Mail\MemberVerificationCode;
use Carbon\Carbon;
use App\Traits\FormatsMembershipReceipt;
use Illuminate\Support\Facades\Schema;

class MemberAuthController extends Controller
{
    use FormatsMembershipReceipt;

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
                $hasActiveMembership = MembershipPayment::where('user_id', $user->id)
                    ->where('isapproved', 1)
                    ->where(function ($query) {
                        $query->whereNull('expiration_at')
                              ->orWhere('expiration_at', '>', now());
                    })
                    ->exists();

                $pendingMembership = MembershipPayment::where('user_id', $user->id)
                    ->where('isapproved', 0)
                    ->latest()
                    ->first();
                if ($pendingMembership) {
                    $pendingMembership->loadMissing('membership');
                }

                if (! $hasActiveMembership) {
                    Auth::logout();

                    $pendingResponse = $pendingMembership
                        ? $this->formatMembershipReceipt($pendingMembership)
                        : null;

                    $message = $pendingMembership
                        ? 'Your membership payment is pending. Please visit the gym to complete your payment.'
                        : 'You currently do not have an active membership. Please visit the gym to activate your account again.';

                    return response()->json([
                        'message' => $message,
                        'pending_membership' => $pendingResponse,
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
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'address' => 'required|string',
            'phone_number' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed'],
            'membership_id' => 'required|exists:memberships,id',
        ]);

        $membership = Membership::find($validated['membership_id']);

        $user = DB::transaction(function () use ($validated, $membership) {
            $user = new User();
            $user->role_id = 3;
            $user->status_id = 2;
            $user->first_name = $validated['first_name'];
            $user->last_name = $validated['last_name'];
            $user->address = $validated['address'];
            $user->phone_number = $validated['phone_number'];
            $user->email = $validated['email'];
            $user->password = Hash::make($validated['password']);
            $user->save();

            $prefix = match ((int) $user->role_id) {
                1 => 'A',
                2 => 'S',
                3 => 'M',
                4 => 'SA',
                5 => 'T',
                default => '',
            };
            $user->user_code = $prefix . $user->id;
            $user->save();

            $membershipPayment = new MembershipPayment();
            $membershipPayment->user_id = $user->id;
            $membershipPayment->membership_id = $membership->id;
            $membershipPayment->isapproved = 0;
            $membershipPayment->proof_of_payment = 'pending';
            $membershipPayment->expiration_at = $this->calculateExpiration($membership);
            if (Schema::hasColumn('membership_payments', 'created_by')) {
                $membershipPayment->created_by = sprintf(
                    '%s %s (mobile signup)',
                    $user->first_name,
                    $user->last_name
                );
            }
            $membershipPayment->save();

            $user->setRelation('pending_membership', $membershipPayment);

            return $user;
        });

        $token = $user->createToken('member_fithub_token')->plainTextToken;

        $pendingMembership = $user->pending_membership ?? null;

        $response = [
            'token' => $token,
            'user' => $user->withoutRelations(),
            'receipt' => $pendingMembership ? $this->formatMembershipReceipt($pendingMembership) : null,
        ];

        return response()->json([
            'message' => 'Registration successful. Please visit the gym to complete your payment.',
            'response' => $response,
        ]);
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

    private function calculateExpiration(Membership $membership): ?Carbon
    {
        $expiry = Carbon::now();

        $hasDuration = false;

        if (!empty($membership->year)) {
            $expiry->addYears((int) $membership->year);
            $hasDuration = true;
        }
        if (!empty($membership->month)) {
            $expiry->addMonths((int) $membership->month);
            $hasDuration = true;
        }
        if (!empty($membership->week)) {
            $expiry->addWeeks((int) $membership->week);
            $hasDuration = true;
        }

        return $hasDuration ? $expiry : null;
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
