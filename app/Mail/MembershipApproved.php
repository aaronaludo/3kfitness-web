<?php

namespace App\Mail;

use App\Models\MembershipPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class MembershipApproved extends Mailable
{
    use Queueable, SerializesModels;

    public string $memberName;
    public string $membershipName;
    public ?string $approvedAt;
    public ?string $expirationAt;
    public ?string $approvedBy;

    /**
     * Create a new message instance.
     */
    public function __construct(MembershipPayment $payment, ?string $approvedBy = null)
    {
        $member = $payment->user;
        $fullName = trim(collect([$member->first_name ?? '', $member->last_name ?? ''])->filter()->implode(' '));

        $this->memberName = $fullName !== '' ? $fullName : ($member->email ?? 'Member');
        $this->membershipName = optional($payment->membership)->name ?? 'membership';
        $this->approvedAt = $payment->updated_at
            ? Carbon::parse($payment->updated_at)->format('F j, Y g:i A')
            : null;
        $this->expirationAt = $payment->expiration_at
            ? Carbon::parse($payment->expiration_at)->format('F j, Y g:i A')
            : null;
        $this->approvedBy = $approvedBy ? trim($approvedBy) : null;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your membership is approved')
            ->view('emails.membership-approved');
    }
}
