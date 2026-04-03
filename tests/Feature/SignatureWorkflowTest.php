<?php

use App\Enums\SignatureStatus;
use App\Jobs\DocumentSignature\SealSignedDocumentJob;
use App\Livewire\PublicSignaturePortal;
use App\Models\DocumentSignature;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Notification::fake();
});

test('un document expiré ne peut pas être signé', function () {
    // 1. Arrange : Création d'un document expiré via la Factory
    $document = DocumentSignature::factory()->create([
        'status' => SignatureStatus::EXPIRED,
        'expires_at' => now()->subDays(2),
    ]);

    // 2. Act : On tente de soumettre une signature via le composant Livewire ou le Controller
    // (Hypothèse d'utilisation de Livewire pour le portail public)
    Livewire::test(PublicSignaturePortal::class, ['document' => $document, 'uuid' => $document->uuid])
        // Utilisation de fillForm au lieu de set() pour les formulaires Filament
        ->fillForm([
            'signatureBase64' => 'data:image/png;base64,fake-signature',
            'signerName' => 'Jean Dupont',
        ])
        ->call('submitSignature') // ⚠️ Remplacez 'submit' par le nom exact de votre méthode
        ->assertNotified(
            Notification::make()
                ->danger()
                ->title('La validité du devis pour signature à expiré, veuillez contacter C2ME au 02 51 38 16 76')
        );

    expect($document->fresh()->status)->toBe(SignatureStatus::EXPIRED);
});

test('un document déjà signé retourne une erreur et bloque la nouvelle signature', function () {
    // 1. Arrange : Création d'un document déjà signé
    $document = DocumentSignature::factory()->create([
        'status' => SignatureStatus::SIGNED,
    ]);

    // 2. Act : On simule la soumission du formulaire
    Livewire::test(PublicSignaturePortal::class, ['document' => $document, 'uuid' => $document->uuid])
        ->fillForm([
            'signatureBase64' => 'data:image/png;base64,fake-signature-new',
            'signerName' => 'Jean Dupont',
        ])
        ->call('submitSignature')
        // 3. Assert : On vérifie qu'une notification d'erreur est envoyée
        ->assertNotified(
            Notification::make()
                ->danger()
                ->title('Ce document a déjà été signé.')
        );

    // On s'assure que le document est toujours considéré comme signé
    expect($document->fresh()->status)->toBe(SignatureStatus::SIGNED);
});

test('une signature valide change le statut et déclenche le scellement PDF (mock)', function () {
    // 1. Arrange : On simule la file d'attente pour ne pas exécuter réellement Ghostscript/FPDI
    Queue::fake();

    // Création d'un document en attente de signature
    $document = DocumentSignature::factory()->create([
        'status' => SignatureStatus::SENT,
    ]);

    // 2. Act : L'utilisateur soumet une signature valide
    Livewire::test(PublicSignaturePortal::class, ['document' => $document, 'uuid' => $document->uuid])
        ->fillForm([
            'signatureBase64' => 'data:image/png;base64,fake-valid-signature',
            'signerName' => 'Jean Dupont',
        ])
        ->call('submitSignature')
        ->assertHasNoFormErrors();

    // 3. Assert :
    // Le document doit avoir été mis à jour correctement par le WorkflowService
    expect($document->fresh()->status)->toBe(SignatureStatus::SIGNED);

    // Le Job de scellement a bien été poussé dans la file d'attente
    Queue::assertPushed(SealSignedDocumentJob::class, function ($job) use ($document) {
        return $job->document->id === $document->id;
    });
});
