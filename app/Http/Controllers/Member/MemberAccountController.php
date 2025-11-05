<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserSchedule;
use App\Traits\ResolvesActiveMembership;
use Illuminate\Support\Facades\Hash;

class MemberAccountController extends Controller
{
    use ResolvesActiveMembership;

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

        $emailChanged = $user->email !== $request->email;

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->phone_number = $request->phone_number;
        $user->address = $request->address;
        $user->email = $request->email;

        if ($emailChanged) {
            $user->is_email_verified = 0;
            $user->email_verification_code = null;
        }
        $user->save();

        return response()->json([
            'user' => $user,
            'membership_usage' => $this->summarizeMonthlyClassUsage($user),
        ]);
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

    protected function summarizeMonthlyClassUsage(User $user): array
    {
        $activeMembership = $this->resolveActiveMembershipForUser($user);
        $classLimit = optional(optional($activeMembership)->membership)->class_limit_per_month;

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $joinedCount = UserSchedule::where('user_id', $user->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        return [
            'class_limit_per_month' => $classLimit,
            'classes_joined_this_month' => $joinedCount,
            'limit_reached' => !is_null($classLimit) && $classLimit > 0 && $joinedCount >= $classLimit,
        ];
    }
}
