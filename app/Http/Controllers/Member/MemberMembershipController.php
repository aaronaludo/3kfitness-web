<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Membership;
use App\Models\MembershipPayment;

class MemberMembershipController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $data = Membership::all();
    
        $membership_payment = MembershipPayment::where('user_id', $user->id)
                                          ->where('expiration_at', '>', now())
                                          ->orderBy('created_at', 'desc')
                                          ->first();
        
        $membership_message = null;
    
        if ($membership_payment) {
            if ($membership_payment->isapproved === 1) {
                $expiry_date = \Carbon\Carbon::parse($membership_payment->expiration_at)->format('m/d/Y h:i A');
                $membership_message = "Your Membership: {$membership_payment->membership->name} is approved until {$expiry_date}";
            } else if ($membership_payment->isapproved === 0) {
                $membership_message = "Your Membership: {$membership_payment->membership->name} is pending, please wait for the admin to approve it";
            }
        } else {
            $membership_message = "You currently do not have an active membership.";
        }
    
        return response()->json([
            'data' => $data,
            'membership_payment' => $membership_payment,
            'membership_message' => $membership_message,
        ]);
    }           

    public function checkout(Request $request)
    {
        $user = $request->user();
        $membership = Membership::find($request->membership_id);
    
        $existingMembership = MembershipPayment::where('user_id', $user->id)
        ->whereIn('isapproved', [0, 1])
        ->where(function ($query) {
            $query->where('isapproved', 0)
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('isapproved', 1)
                               ->where(function ($nestedQuery) {
                                   $nestedQuery->whereNull('expiration_at')
                                               ->orWhere('expiration_at', '>', now());
                               });
                  });
        })
        ->first();
    
    
        if ($existingMembership) {
            return response()->json([
                'message' => 'You already have an active or pending membership. Please wait for it to expire or be approved/rejected before purchasing a new membership.'
            ], 400);
        }
    
        $data = new MembershipPayment;
        $data->user_id = $user->id;
        $data->membership_id = $membership->id;
        $data->isapproved = 0;
        $data->proof_of_payment = 'blank_for_now';
    
        $currentDate = new \DateTime();
        if ($membership->year) {
            $currentDate->modify("+{$membership->year} years");
        }
        if ($membership->month) {
            $currentDate->modify("+{$membership->month} months");
        }
        if ($membership->week) {
            $currentDate->modify("+{$membership->week} weeks");
        }
        $data->expiration_at = $currentDate;

        $data->save();
    
        return response()->json([
            'message' => 'Checkout successful. Your membership is pending approval.',
            'data' => $data
        ]);
    }    
}
