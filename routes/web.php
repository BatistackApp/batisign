<?php

use App\Models\DocumentSignature;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::livewire('/sign/{uuid}', 'public-signature-portal')->name('public.document.sign');

Route::get('/document/{uuid}/preview', function ($uuid) {
    $document = DocumentSignature::where('uuid', $uuid)->firstOrFail();
    $path = Storage::disk('local')->path($document->original_pdf_path);

    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="devis_'.$document->ebp_quote_number.'.pdf"',
    ]);
})->name('public.document.preview');

require __DIR__.'/settings.php';
