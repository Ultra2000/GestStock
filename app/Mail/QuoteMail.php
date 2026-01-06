<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class QuoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public ?string $customMessage = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Devis {$this->quote->quote_number} - {$this->quote->user->company->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote',
        );
    }

    public function attachments(): array
    {
        // Générer le PDF du devis
        $pdf = Pdf::loadView('pdf.quote', ['quote' => $this->quote]);
        
        return [
            Attachment::fromData(fn () => $pdf->output(), "Devis-{$this->quote->quote_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
