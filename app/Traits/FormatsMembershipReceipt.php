<?php

namespace App\Traits;

use App\Models\MembershipPayment;

trait FormatsMembershipReceipt
{
    protected function formatMembershipReceipt(MembershipPayment $payment): array
    {
        $payment->loadMissing('membership', 'user');
        $membership = $payment->membership;

        $currency = $membership->currency ?? 'PHP';
        $price = (float) ($membership->price ?? 0);
        $formattedPrice = sprintf('%s %s', $currency, number_format($price, 2));

        $durationParts = [];
        if (!empty($membership->year)) {
            $years = (int) $membership->year;
            $durationParts[] = $years === 1 ? '1 year' : "{$years} years";
        }
        if (!empty($membership->month)) {
            $months = (int) $membership->month;
            $durationParts[] = $months === 1 ? '1 month' : "{$months} months";
        }
        if (!empty($membership->week)) {
            $weeks = (int) $membership->week;
            $durationParts[] = $weeks === 1 ? '1 week' : "{$weeks} weeks";
        }

        return [
            'payment_id' => $payment->id,
            'membership_id' => $membership->id ?? null,
            'membership_name' => $membership->name ?? null,
            'membership_description' => $membership->description ?? null,
            'currency' => $currency,
            'price' => $price,
            'price_formatted' => $formattedPrice,
            'duration' => implode(' • ', $durationParts),
            'status' => match ((int) $payment->isapproved) {
                1 => 'approved',
                2 => 'rejected',
                default => 'pending',
            },
            'created_at' => optional($payment->created_at)->toIso8601String(),
            'created_at_display' => optional($payment->created_at)->format('F j, Y g:i A'),
            'expires_at' => optional($payment->expiration_at)->toIso8601String(),
            'expires_at_display' => optional($payment->expiration_at)->format('F j, Y g:i A'),
            'instructions' => 'Present this receipt at the gym front desk to complete your payment.',
        ];
    }
}
