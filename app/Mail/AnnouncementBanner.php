<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementBanner extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $message,
        public readonly string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mise à jour FRECORP ERP — ' . now()->translatedFormat('d F Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.announcement-banner',
        );
    }
}
