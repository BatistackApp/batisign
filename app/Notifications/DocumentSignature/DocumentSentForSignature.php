<?php

namespace App\Notifications\DocumentSignature;

use App\Models\DocumentSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSentForSignature extends Notification
{
    use Queueable;

    public function __construct(public DocumentSignature $document) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // URL publique vers votre composant Livewire de signature
        $url = route('public.document.sign', $this->document->uuid);

        return (new MailMessage)
            ->subject('Votre devis est prêt pour signature - '.$this->document->ebp_quote_number)
            ->greeting('Bonjour,')
            ->line('Veuillez trouver ci-joint l\'accès à votre devis en attente de validation.')
            ->action('Consulter et Signer le devis', $url)
            ->line('Ce lien expirera le '.$this->document->expires_at->format('d/m/Y'))
            ->line('Merci de votre confiance !');
    }
}
