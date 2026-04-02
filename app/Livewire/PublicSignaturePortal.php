<?php

namespace App\Livewire;

use App\Models\DocumentSignature;
use App\Services\SignatureWorkflowService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.auth')]
class PublicSignaturePortal extends Component
{
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
        $this->validate();

        $workflowService->processClientSignature(
            $this->document,
            $this->signatureBase64,
            $this->signerName,
            request()->ip()
        );

        $this->document->refresh();
    }

    public function downloadPdf(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return response()->download(
            Storage::disk('local')->path($this->document->original_pdf_path),
            'Devis_' . $this->document->ebp_quote_number . '.pdf'
        );
    }

    public function render()
    {
        return view('livewire.public-signature-portal');
    }
}
