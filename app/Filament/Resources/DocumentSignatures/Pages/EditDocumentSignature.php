<?php

namespace App\Filament\Resources\DocumentSignatures\Pages;

use App\Filament\Resources\DocumentSignatures\DocumentSignatureResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentSignature extends EditRecord
{
    protected static string $resource = DocumentSignatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
