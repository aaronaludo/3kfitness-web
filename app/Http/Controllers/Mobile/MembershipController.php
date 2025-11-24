<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Membership;
use App\Models\MembershipPayment;
use Illuminate\Support\Facades\Schema;
use App\Traits\ResolvesActiveMembership;
use Carbon\Carbon;

class MembershipController extends Controller
{
    use ResolvesActiveMembership;

    public function index()
    {
        $memberships = Membership::select('id', 'name', 'currency', 'description', 'price', 'year', 'month', 'week', 'class_limit_per_month')
            ->when(Schema::hasColumn('memberships', 'is_archive'), fn ($query) => $query->where('is_archive', 0))
            ->orderBy('price')
            ->get();

        if ($memberships->isEmpty()) {
            return response()->json(['message' => 'Membership list is empty']);
        }

        return response()->json(['data' => $memberships]);
    }

    public function status(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $activeMembership = $this->resolveActiveMembershipForUser($user);

        $pendingMembership = MembershipPayment::with('membership')
            ->where('user_id', $user->id)
            ->where('isapproved', 0)
            ->when(Schema::hasColumn('membership_payments', 'is_archive'), fn ($query) => $query->where('is_archive', 0))
            ->latest()
            ->first();

        $message = 'You currently do not have an active membership.';

        if ($activeMembership) {
            $expiresAt = optional($activeMembership->expiration_at)->format('m/d/Y h:i A');
            $message = 'Your membership is active' . ($expiresAt ? " until {$expiresAt}" : '');
        } elseif ($pendingMembership) {
            $message = "Your membership: {$pendingMembership->membership->name} is pending, please visit the gym to complete your payment.";
        }

        return response()->json([
            'active_membership' => $activeMembership ? $this->transformMembershipPayment($activeMembership) : null,
            'pending_membership' => $pendingMembership ? $this->transformMembershipPayment($pendingMembership) : null,
            'message' => $message,
        ]);
    }

    private function transformMembershipPayment(MembershipPayment $payment): array
    {
        $payment->loadMissing('membership');

        $expiresAt = $payment->expiration_at ? Carbon::parse($payment->expiration_at) : null;
        $startedAt = $payment->created_at ? Carbon::parse($payment->created_at) : null;
        $now = Carbon::now();

        $remainingSeconds = $expiresAt ? max($now->diffInSeconds($expiresAt, false), 0) : null;
        $totalSeconds = ($expiresAt && $startedAt) ? max($expiresAt->diffInSeconds($startedAt, false), 0) : null;

        $progress = null;
        if ($totalSeconds && $totalSeconds > 0) {
            $progress = min(max(($totalSeconds - $remainingSeconds) / $totalSeconds, 0), 1);
        } elseif (!$expiresAt) {
            $progress = 1;
        }

        return [
            'id' => $payment->id,
            'membership' => $payment->membership,
            'isapproved' => (int) $payment->isapproved,
            'started_at' => optional($startedAt)->toIso8601String(),
            'expires_at' => optional($expiresAt)->toIso8601String(),
            'remaining_seconds' => $remainingSeconds,
            'remaining_days' => $remainingSeconds !== null ? (int) ceil($remainingSeconds / 86400) : null,
            'total_seconds' => $totalSeconds,
            'progress' => $progress ?? ($totalSeconds === 0 ? 1 : $progress),
        ];
    }
}
