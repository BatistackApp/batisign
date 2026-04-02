<?php

namespace App\Notifications\DocumentSignature;

use App\Models\DocumentSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class DocumentSuccessfullySigned extends Notification
{
    use Queueable;

    public function __construct(public DocumentSignature $document) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $filePath = Storage::disk('local')->path($this->document->signed_pdf_path);

        return (new MailMessage)
            ->subject('Copie de votre devis signé - '.$this->document->ebp_quote_number)
            ->greeting('Bonjour,')
            ->line('Nous vous confirmons la bonne réception de votre signature.')
            ->line('Vous trouverez en pièce jointe le document contractuel scellé.')
            ->attach($filePath, [
                'as' => 'Devis_Signe_'.$this->document->ebp_quote_number.'.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
