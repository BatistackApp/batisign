<?php

namespace App\Livewire;

use App\Enums\SignatureStatus;
use App\Models\DocumentSignature;
use App\Services\SignatureWorkflowService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts.base')]
class PublicSignaturePortal extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public DocumentSignature $document;

    public ?array $data = [];

    public function mount($uuid, SignatureWorkflowService $workflowService): void
    {
        $this->document = DocumentSignature::where('uuid', $uuid)->firstOrFail();
        $workflowService->markAsViewed($this->document);

        // Initialisation du formulaire vide
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('signerName')
                    ->label('Nom et Prénom du signataire')
                    ->placeholder('Ex: Jean Dupont')
                    ->required()
                    ->minLength(3)
                    ->maxLength(100),

                SignaturePad::make('signatureBase64')
                    ->label('Votre signature')
                    ->required()
                    ->penColor('#111827') // Couleur de l'encre
                    ->backgroundColor('#f9fafb'), // Fond légèrement gris
            ])
            ->statePath('data'); // Lie les champs au tableau $data
    }

    public function submitSignature(SignatureWorkflowService $workflowService): void
    {
        // Validation automatique via Filament
        $validatedData = $this->form->getState();

        if ($this->document->status === SignatureStatus::EXPIRED) {
            Notification::make()
                ->danger()
                ->title('La validité du devis pour signature à expiré, veuillez contacter C2ME au 02 51 38 16 76')
                ->send();

            return;
        }

        if ($this->document->status === SignatureStatus::SIGNED) {
            Notification::make()
                ->danger()
                ->title('Ce document a déjà été signé.')
                ->send();
        }

        // Appel au service métier
        $workflowService->processClientSignature(
            $this->document,
            $validatedData['signatureBase64'],
            $validatedData['signerName'],
            request()->ip()
        );

        $this->document->refresh();
    }

    public function downloadPdf(): BinaryFileResponse
    {
        return response()->download(
            Storage::disk('local')->path($this->document->original_pdf_path),
            'Devis_'.$this->document->ebp_quote_number.'.pdf'
        );
    }

    public function render()
    {
        return view('livewire.public-signature-portal');
    }
}
