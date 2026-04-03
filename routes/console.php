<?php

use App\Enums\SignatureStatus;
use App\Mail\SignatureReminderMail;
use App\Models\DocumentSignature;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('signatures:remind', function () {
    $this->info('Début du traitement des relances de signatures...');

    // Règle métier : Envoyer UN SEUL email de rappel si le devis est SENT
    // depuis plus de 7 jours, non EXPIRED, et n'a jamais été relancé.
    $documentsToRemind = DocumentSignature::query()
        ->where('status', SignatureStatus::SENT)
        ->where('created_at', '<=', now()->subDays(7))
        ->whereNull('reminded_at') // <--- NOUVEAU: S'assure qu'on ne relance qu'une seule fois
        ->where(function ($query) {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        })
        ->get();

    $count = 0;

    foreach ($documentsToRemind as $document) {
        try {
            Mail::to($document->client_email)->send(new SignatureReminderMail($document));

            // On marque le document comme relancé pour ne plus le sélectionner demain
            $document->update(['reminded_at' => now()]);

            $this->line("Relance envoyée pour le document ID : {$document->id} (Client: {$document->client_name})");
            $count++;

        } catch (Exception $e) {
            Log::error("Erreur lors de la relance du document ID {$document->id} : ".$e->getMessage());
            $this->error("Erreur pour le document ID : {$document->id}");
        }
    }

    $this->info("Traitement terminé. {$count} relance(s) effectuée(s).");
})->purpose('Envoie des emails de relance pour les documents en attente depuis plus de 7 jours');

Schedule::call(function () {
    $expiredCount = DocumentSignature::whereIn('status', [SignatureStatus::PENDING, SignatureStatus::SENT, SignatureStatus::VIEWED])
        ->where('expires_at', '<', now())
        ->update(['status' => SignatureStatus::EXPIRED]);

    if ($expiredCount > 0) {
        Log::info("{$expiredCount} documents de signature ont expiré aujourd'hui.");
    }
})->dailyAt('02:00')->name('expire-signatures')->withoutOverlapping();

Schedule::command('signatures:remind')->dailyAt('08:00');
