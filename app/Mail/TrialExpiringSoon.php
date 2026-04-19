<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialExpiringSoon extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Company $company,
        public readonly int $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⏰ Plus que {$this->daysLeft} jour(s) pour votre évaluation FRECORP",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-expiring-soon',
        );
    }
}
