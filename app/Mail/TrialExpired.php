<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialExpired extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Company $company,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🔒 Votre période d'évaluation FRECORP est terminée",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-expired',
        );
    }
}
