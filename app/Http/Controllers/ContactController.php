<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'message' => 'required|string|max:2000',
        ]);

        try {
            $payload = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'content' => $validated['message'],
            ];

            Mail::send('emails.contact', $payload, function ($mail) use ($validated) {
                $mail->to('alecconado@gmail.com')
                    ->replyTo($validated['email'], $validated['name'])
                    ->subject('3K Fitness Contact: ' . $validated['name']);
            });
        } catch (\Throwable $th) {
            return redirect()
                ->to(url('/#contact'))
                ->withInput()
                ->withErrors(['contact' => 'Unable to send your message right now. Please try again later.']);
        }

        return redirect()
            ->to(url('/#contact'))
            ->with('contact_success', 'Message sent successfully. We will get back to you soon.');
    }
}
