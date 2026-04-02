<?php

namespace App\Services;

use App\Enums\SignatureStatus;
use App\Models\DocumentSignature;

class SignatureWorkflowService
{
    /**
     * Traite la validation de la signature par le client.
     */
    public function processClientSignature(
        DocumentSignature $document,
        string $signatureBase64,
        string $signerName,
        string $clientIp
    ): void {
        // 1. Sécurité : Vérifier que le devis peut encore être signé
        if ($document->status === SignatureStatus::SIGNED || $document->status === SignatureStatus::EXPIRED) {
            abort(403, 'Ce document ne peut plus être signé.');
        }

        // 2. Enregistrement des données en base
        $document->update([
            'status' => SignatureStatus::SIGNED,
            'signature_data' => $signatureBase64,
            'signer_name' => $signerName,
            'signer_ip' => $clientIp,
            'signed_at' => now(),
        ]);

        // 3. Déclenchement asynchrone de la génération du PDF scellé
        // Note: Nous commenterons cette ligne en attendant de créer le Job (Étape 5)
        // SealSignedDocumentJob::dispatch($document);
    }

    /**
     * Marque le document comme consulté (utile pour savoir si le client a ouvert le mail).
     */
    public function markAsViewed(DocumentSignature $document): void
    {
        if ($document->status === SignatureStatus::SENT) {
            $document->update(['status' => SignatureStatus::VIEWED]);
        }
    }

    /**
     * Révoque un lien de signature.
     */
    public function expireDocument(DocumentSignature $document): void
    {
        if (in_array($document->status, [SignatureStatus::PENDING, SignatureStatus::SENT, SignatureStatus::VIEWED])) {
            $document->update(['status' => SignatureStatus::EXPIRED]);
        }
    }
}
