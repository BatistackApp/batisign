<?php

namespace App\Observers;

use App\Models\DocumentSignature;
use Str;

class DocumentSignatureObserver
{
    public function creating(DocumentSignature $documentSignature): void
    {
        // Génère un UUID unique s'il n'est pas déjà défini
        if (empty($documentSignature->uuid)) {
            $documentSignature->uuid = Str::uuid()->toString();
        }

        // Définit une date d'expiration par défaut (30 jours) si non spécifiée
        if (empty($documentSignature->expires_at)) {
            $documentSignature->expires_at = now()->addDays(30);
        }
    }
}
