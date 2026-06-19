<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Tu pedido fue confirmado! #' . str_pad((string) $this->order->id, 8, '0', STR_PAD_LEFT),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-confirmation');
    }

    public function attachments(): array
    {
        return [];
    }
}
