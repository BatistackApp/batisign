<?php

use App\Models\DocumentSignature;
use App\Services\SignatureWorkflowService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.auth')]
class extends Component {
    public DocumentSignature $document;

    #[Validate('required|string|min:3|max:100', message: 'Veuillez saisir votre nom et prénom.')]
    public string $signerName = '';

    #[Validate('required|string|starts_with:data:image', message: 'Veuillez dessiner votre signature dans le cadre prévu.')]
    public string $signatureBase64 = '';

    public function mount($uuid, SignatureWorkflowService $workflowService): void
    {
        $this->document = DocumentSignature::where('uuid', $uuid)->firstOrFail();

        // Marquer comme consulté si c'est la première ouverture
        $workflowService->markAsViewed($this->document);
    }

    public function submitSignature(SignatureWorkflowService $workflowService): void
    {
        // La validation se base désormais sur les attributs #[Validate]
        $this->validate();

        // Appel au service métier défini à l'étape 4
        $workflowService->processClientSignature(
            $this->document,
            $this->signatureBase64,
            $this->signerName,
            request()->ip()
        );

        // Rafraîchir l'instance pour mettre à jour l'affichage
        $this->document->refresh();
    }
};
?>

<div class="min-h-screen bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-md overflow-hidden">

        <!-- En-tête -->
        <div class="bg-blue-600 px-6 py-4">
            <h1 class="text-xl font-bold text-white">Validation du devis {{ $document->ebp_quote_number }}</h1>
        </div>

        <div class="p-6">
            <!-- Gestion des statuts (Expiré, Signé) -->
            @if($document->status === \App\Enums\SignatureStatus::EXPIRED)
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg">
                    Ce lien de signature a expiré. Veuillez contacter votre artisan pour obtenir un nouveau lien.
                </div>
            @elseif($document->status === \App\Enums\SignatureStatus::SIGNED)
                <div class="bg-green-50 border border-green-200 text-green-700 p-6 rounded-lg text-center">
                    <svg class="mx-auto h-12 w-12 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <h2 class="text-2xl font-bold mb-2">Devis signé avec succès !</h2>
                    <p>Merci pour votre validation. Vous allez recevoir une copie du document par email d'ici quelques instants.</p>
                </div>
            @else

                <!-- Zone de prévisualisation du PDF (Optionnelle, nécessite une route de téléchargement) -->
                <div class="mb-8 border border-gray-200 rounded bg-gray-50 p-4 text-center">
                    <p class="text-gray-600 mb-2">Veuillez prendre connaissance du devis avant de le signer.</p>
                    <a href="#" class="text-blue-600 hover:underline font-medium">
                        📥 Télécharger / Voir le devis PDF
                    </a>
                </div>

                <!-- Formulaire de signature -->
                <form wire:submit="submitSignature" class="space-y-6">

                    <!-- Nom du signataire -->
                    <div>
                        <label for="signerName" class="block text-sm font-medium text-gray-700">Nom et Prénom du signataire</label>
                        <input type="text" id="signerName" wire:model="signerName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-3 border" placeholder="Ex: Jean Dupont">
                        @error('signerName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Composant Alpine.js pour le Canvas de signature -->
                    <div
                        x-data="{
                            signaturePad: null,
                            init() {
                                // Chargement asynchrone du script si non présent
                                if (typeof SignaturePad === 'undefined') {
                                    let script = document.createElement('script');
                                    script.src = '[https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js](https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js)';
                                    script.onload = () => this.setupPad();
                                    document.head.appendChild(script);
                                } else {
                                    this.setupPad();
                                }
                            },
                            setupPad() {
                                let canvas = this.$refs.signatureCanvas;
                                // Ajustement pour les écrans haute définition (Retina)
                                let ratio =  Math.max(window.devicePixelRatio || 1, 1);
                                canvas.width = canvas.offsetWidth * ratio;
                                canvas.height = canvas.offsetHeight * ratio;
                                canvas.getContext('2d').scale(ratio, ratio);

                                this.signaturePad = new SignaturePad(canvas, {
                                    backgroundColor: 'rgb(249, 250, 251)', // bg-gray-50
                                    penColor: 'rgb(0, 0, 0)'
                                });

                                // Met à jour Livewire quand on finit de dessiner
                                this.signaturePad.addEventListener('endStroke', () => {
                                    if(!this.signaturePad.isEmpty()) {
                                        @this.set('signatureBase64', this.signaturePad.toDataURL('image/png'));
                                    }
                                });
                            },
                            clearPad() {
                                this.signaturePad.clear();
                                @this.set('signatureBase64', '');
                            }
                        }"
                    >
                        <label class="block text-sm font-medium text-gray-700 mb-2">Votre signature (Dessinez ci-dessous)</label>

                        <div class="relative border-2 border-dashed border-gray-300 rounded-lg overflow-hidden">
                            <canvas x-ref="signatureCanvas" class="w-full h-48 sm:h-64 cursor-crosshair touch-none"></canvas>
                        </div>

                        <div class="flex justify-between mt-2">
                            <button type="button" @click="clearPad()" class="text-sm text-red-600 hover:text-red-800 font-medium">Effacer la signature</button>
                            @error('signatureBase64') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Bouton de soumission -->
                    <div class="pt-4">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                            Confirmer et Signer le devis
                        </button>

                        <!-- Indicateur de chargement -->
                        <div wire:loading wire:target="submitSignature" class="mt-2 text-center text-sm text-gray-500 w-full">
                            Traitement et sécurisation de votre signature en cours...
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
