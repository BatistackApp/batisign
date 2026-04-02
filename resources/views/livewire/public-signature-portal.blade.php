<div class="w-full max-w-xl mx-auto bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">

    <!-- En-tête -->
    <div class="bg-indigo-600 px-6 py-5">
        <h1 class="text-xl font-bold text-white">Validation du devis</h1>
        <p class="text-indigo-100 text-sm mt-1">Réf: {{ $document->ebp_quote_number }}</p>
    </div>

    <div class="p-6 sm:p-8">
        <!-- Gestion des statuts (Expiré, Signé) -->
        @if($document->status === \App\Enums\SignatureStatus::EXPIRED)
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-md">
                <p class="font-bold">Lien expiré</p>
                <p class="text-sm mt-1">Ce lien de signature a expiré. Veuillez contacter votre artisan pour en obtenir un nouveau.</p>
            </div>
        @elseif($document->status === \App\Enums\SignatureStatus::SIGNED)
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-6 rounded-lg text-center shadow-sm">
                <svg class="mx-auto h-16 w-16 text-emerald-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h2 class="text-2xl font-bold mb-2">Devis signé avec succès !</h2>
                <p class="text-emerald-700">Merci pour votre confiance. Une copie du document finalisée vous sera envoyée par email.</p>
            </div>
        @else

            <!-- Prévisualisation du devis -->
            <div class="mb-8 border border-gray-200 rounded-lg overflow-hidden bg-white shadow-sm">
                <div class="p-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <p class="text-gray-700 font-medium text-sm">Veuillez prendre connaissance du devis</p>
                    <button type="button" wire:click="downloadPdf" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Télécharger
                    </button>
                </div>
                <!-- Iframe pour afficher le PDF -->
                <iframe src="{{ route('public.document.preview', $document->uuid) }}" class="w-full h-full border-0" title="Prévisualisation du devis">
                    <p class="text-center p-4">Votre navigateur ne supporte pas l'affichage des PDF. <button type="button" wire:click="downloadPdf" class="text-indigo-600 underline">Téléchargez-le ici</button>.</p>
                </iframe>
            </div>

            <!-- Formulaire -->
            <form wire:submit="submitSignature" class="space-y-6">

                <!-- Nom -->
                <div>
                    <label for="signerName" class="block text-sm font-semibold text-gray-700 mb-1">Nom et Prénom du signataire <span class="text-red-500">*</span></label>
                    <input type="text" id="signerName" wire:model="signerName" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-3 border" placeholder="Ex: Jean Dupont">
                    @error('signerName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Zone de signature (Alpine.js) -->
                <div
                    x-data="{
                        signaturePad: null,
                        init() {
                            if (typeof SignaturePad === 'undefined') {
                                let script = document.createElement('script');
                                script.src = 'https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js';
                                script.onload = () => this.setupPad();
                                document.head.appendChild(script);
                            } else {
                                this.setupPad();
                            }
                        },
                        setupPad() {
                            let canvas = this.$refs.signatureCanvas;
                            // Fixer la taille du canvas par rapport à son conteneur
                            canvas.width = canvas.offsetWidth;
                            canvas.height = 200; // Hauteur fixe en pixels

                            this.signaturePad = new SignaturePad(canvas, {
                                backgroundColor: 'rgb(249, 250, 251)', // bg-gray-50
                                penColor: 'rgb(17, 24, 39)' // text-gray-900
                            });

                            this.signaturePad.addEventListener('endStroke', () => {
                                if(!this.signaturePad.isEmpty()) {
                                    $wire.set('signatureBase64', this.signaturePad.toDataURL('image/png'));
                                }
                            });
                        },
                        clearPad() {
                            this.signaturePad.clear();
                            $wire.set('signatureBase64', '');
                        }
                    }"
                    class="mt-4"
                >
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Votre signature <span class="text-red-500">*</span></label>

                    <div wire:ignore class="relative border-2 border-dashed border-gray-300 rounded-lg overflow-hidden bg-gray-50">
                        <canvas x-ref="signatureCanvas" class="w-full touch-none cursor-crosshair" style="height: 200px;"></canvas>
                    </div>

                    <div class="flex justify-between items-center mt-2">
                        <button type="button" @click="clearPad()" class="text-sm text-red-600 hover:text-red-800 font-medium transition-colors flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Effacer la signature
                        </button>
                        @error('signatureBase64') <span class="text-red-500 text-xs font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Bouton de validation -->
                <div class="pt-6 border-t border-gray-100">
                    <button type="submit" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-lg font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Signer et valider le devis
                    </button>

                    <div wire:loading wire:target="submitSignature" class="mt-3 text-center text-sm font-medium text-indigo-600 w-full animate-pulse">
                        Traitement sécurisé de votre signature en cours...
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
