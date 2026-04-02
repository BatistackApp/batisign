<?php

namespace App\Jobs\DocumentSignature;

use App\Models\DocumentSignature;
use App\Notifications\DocumentSignature\DocumentSuccessfullySigned;
use App\Services\PdfStamperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Log;

class SealSignedDocumentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public DocumentSignature $document) {}

    /**
     * Execute the job.
     */
    public function handle(PdfStamperService $stamper): void
    {
        try {
            // 1. Appose la signature sur le PDF
            $newPdfPath = $stamper->stampSignature($this->document);

            // 2. Met à jour la base de données
            $this->document->update([
                'signed_pdf_path' => $newPdfPath,
            ]);

            // 3. Envoie la notification (Email) au client sans modèle Client (via Notification Routing)
            // Cela nécessite d'avoir ajouté un champ 'client_email' dans la table document_signatures (Étape 2)
            if ($this->document->client_email) {
                Notification::route('mail', $this->document->client_email)
                    ->notify(new DocumentSuccessfullySigned($this->document));
            }

        } catch (\Exception $e) {
            Log::error("Erreur lors du scellement du document {$this->document->uuid}: ".$e->getMessage());
            // Possibilité de notifier l'admin qu'un PDF n'a pas pu être généré
        }
    }
}
