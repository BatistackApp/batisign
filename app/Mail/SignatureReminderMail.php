<?php

namespace App\Mail;

use App\Models\DocumentSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignatureReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public DocumentSignature $document,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Relance : Votre devis est en attente de signature',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.signature-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
