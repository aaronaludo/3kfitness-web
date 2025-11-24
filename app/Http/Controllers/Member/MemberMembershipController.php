<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Traits\FormatsMembershipReceipt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
        $request->validate([
            'membership_id' => 'required|exists:memberships,id',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->handleCheckoutForUser($user, (int) $request->membership_id, 'mobile checkout');
    }

    public function checkoutFromLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'membership_id' => 'required|exists:memberships,id',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ((int) $user->role_id !== 3) {
            Auth::logout();
            return response()->json(['message' => 'Member account only'], 403);
        }

        if ((int) $user->status_id === 1) {
            Auth::logout();
            return response()->json(['message' => 'Your account is pending'], 403);
        }

        return $this->handleCheckoutForUser($user, (int) $request->membership_id, 'mobile checkout (sign-in)');
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

    protected function handleCheckoutForUser($user, int $membershipId, string $createdBySuffix)
    {
        $membership = Membership::find($membershipId);

        if (! $membership) {
            return response()->json(['message' => 'Membership not found'], 404);
        }

        $existingMembership = MembershipPayment::where('user_id', $user->id)
            ->whereIn('isapproved', [0, 1])
            ->when(Schema::hasColumn('membership_payments', 'is_archive'), fn ($query) => $query->where('is_archive', 0))
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
            ->latest()
            ->first();

        if ($existingMembership) {
            return response()->json([
                'message' => 'You already have an active or pending membership. Please wait for it to expire or be approved/rejected before purchasing a new membership.',
                'pending_membership' => $this->formatMembershipReceipt($existingMembership),
            ], 400);
        }

        $payment = new MembershipPayment;
        $payment->user_id = $user->id;
        $payment->membership_id = $membership->id;
        $payment->isapproved = 0;
        $payment->proof_of_payment = 'blank_for_now';
        $payment->expiration_at = $this->calculateExpiration($membership);

        if (Schema::hasColumn('membership_payments', 'created_by')) {
            $payment->created_by = trim(sprintf(
                '%s %s (%s)',
                $user->first_name,
                $user->last_name,
                $createdBySuffix
            ));
        }

        $payment->save();

        return response()->json([
            'message' => 'Checkout successful. Your membership is pending approval.',
            'data' => $payment,
            'receipt' => $this->formatMembershipReceipt($payment),
        ]);
    }

    protected function calculateExpiration(Membership $membership): ?Carbon
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
}
