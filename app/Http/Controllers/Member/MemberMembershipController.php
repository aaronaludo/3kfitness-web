<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Traits\FormatsMembershipReceipt;
use Illuminate\Support\Facades\Schema;

class MemberMembershipController extends Controller
{
    use FormatsMembershipReceipt;

    public function index(Request $request)
    {
        $user = $request->user();
        $data = Membership::select('id', 'name', 'currency', 'description', 'price', 'year', 'month', 'week', 'class_limit_per_month')
            ->when(Schema::hasColumn('memberships', 'is_archive'), fn ($query) => $query->where('is_archive', 0))
            ->orderBy('price')
            ->get();

        $activeMembership = MembershipPayment::with('membership')
            ->where('user_id', $user->id)
            ->where('isapproved', 1)
            ->where(function ($query) {
                $query->whereNull('expiration_at')
                      ->orWhere('expiration_at', '>', now());
            })
            ->when(Schema::hasColumn('membership_payments', 'is_archive'), fn ($query) => $query->where('is_archive', 0))
            ->latest()
            ->first();

        $pendingMembership = MembershipPayment::with('membership')
            ->where('user_id', $user->id)
            ->where('isapproved', 0)
            ->when(Schema::hasColumn('membership_payments', 'is_archive'), fn ($query) => $query->where('is_archive', 0))
            ->latest()
            ->first();

        $membership_message = "You currently do not have an active membership.";

        if ($activeMembership) {
            $expiryDate = optional($activeMembership->expiration_at)->format('m/d/Y h:i A');
            $membership_message = "Your Membership: {$activeMembership->membership->name} is approved" . ($expiryDate ? " until {$expiryDate}" : '');
        } elseif ($pendingMembership) {
            $membership_message = "Your Membership: {$pendingMembership->membership->name} is pending, please visit the gym to complete your payment.";
        }

        return response()->json([
            'data' => $data,
            'membership_payment' => $activeMembership,
            'membership_message' => $membership_message,
            'pending_membership' => $pendingMembership ? $this->formatMembershipReceipt($pendingMembership) : null,
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
        if (Schema::hasColumn('membership_payments', 'created_by')) {
            $data->created_by = sprintf(
                '%s %s (mobile checkout)',
                $user->first_name,
                $user->last_name
            );
        }

        $data->save();
    
        return response()->json([
            'message' => 'Checkout successful. Your membership is pending approval.',
            'data' => $data
        ]);
    }

    public function catalog()
    {
        $memberships = Membership::select('id', 'name', 'currency', 'description', 'price', 'year', 'month', 'week', 'class_limit_per_month')
            ->when(Schema::hasColumn('memberships', 'is_archive'), fn ($query) => $query->where('is_archive', 0))
            ->orderBy('price')
            ->get();

        return response()->json([
            'data' => $memberships,
        ]);
    }
}
