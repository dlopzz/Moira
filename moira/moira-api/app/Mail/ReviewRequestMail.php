<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection  $reviews  Review models with 'product' loaded
     */
    public function __construct(
        public Order $order,
        public Collection $reviews,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¿Qué te pareció tu compra? Dejá tu reseña',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.review-request',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
