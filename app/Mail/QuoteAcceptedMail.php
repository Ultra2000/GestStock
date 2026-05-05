<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "✅ Devis {$this->quote->quote_number} accepté — {$this->quote->customer?->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-accepted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
