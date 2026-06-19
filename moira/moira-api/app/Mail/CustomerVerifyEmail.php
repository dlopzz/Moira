<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CustomerVerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verificationUrl;

    public function __construct(public Customer $customer)
    {
        $this->verificationUrl = URL::temporarySignedRoute(
            'api.v1.auth.verify-email',
            now()->addHours(24),
            [
                'id'   => $customer->id,
                'hash' => sha1($customer->email),
            ],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verificá tu cuenta');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verify-email');
    }
}
