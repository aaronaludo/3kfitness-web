<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MemberVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The verification code to include in the email.
     *
     * @var string
     */
    public string $code;

    /**
     * Create a new message instance.
     *
     * @param  string  $code
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your 3kfitness Verification Code')
            ->view('emails.member-verification-code')
            ->with([
                'code' => $this->code,
            ]);
    }
}
