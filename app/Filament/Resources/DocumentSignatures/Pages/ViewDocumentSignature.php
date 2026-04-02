<?php

namespace App\Filament\Resources\DocumentSignatures\Pages;

use App\Filament\Resources\DocumentSignatures\DocumentSignatureResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentSignature extends ViewRecord
{
    protected static string $resource = DocumentSignatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
