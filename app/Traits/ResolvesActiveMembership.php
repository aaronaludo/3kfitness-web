<?php

namespace App\Traits;

use App\Models\MembershipPayment;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

trait ResolvesActiveMembership
{
    protected function resolveActiveMembershipForUser(User $user): ?MembershipPayment
    {
        return MembershipPayment::with('membership')
            ->where('user_id', $user->id)
            ->whereIn('isapproved', [1, 2]) // treat both approved status codes as valid
            ->when(
                Schema::hasColumn('membership_payments', 'is_archive'),
                fn ($query) => $query->where('is_archive', 0)
            )
            ->where(function ($query) {
                $now = now();
                $query->whereNull('expiration_at')
                    ->orWhere('expiration_at', '>', $now);
            })
            ->latest('updated_at')
            ->first();
    }
}
