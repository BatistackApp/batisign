<?php

namespace App\Filament\Resources\DocumentSignatures;

use App\Filament\Resources\DocumentSignatures\Pages\CreateDocumentSignature;
use App\Filament\Resources\DocumentSignatures\Pages\EditDocumentSignature;
use App\Filament\Resources\DocumentSignatures\Pages\ListDocumentSignatures;
use App\Filament\Resources\DocumentSignatures\Pages\ViewDocumentSignature;
use App\Filament\Resources\DocumentSignatures\Schemas\DocumentSignatureForm;
use App\Filament\Resources\DocumentSignatures\Schemas\DocumentSignatureInfolist;
use App\Filament\Resources\DocumentSignatures\Tables\DocumentSignaturesTable;
use App\Models\DocumentSignature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class DocumentSignatureResource extends Resource
{
    protected static ?string $model = DocumentSignature::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::FileCloud;
    protected static ?string $navigationLabel = 'Signatures Devis';
    protected static ?string $modelLabel = 'Demande de signature';

    public static function form(Schema $schema): Schema
    {
        return DocumentSignatureForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocumentSignatureInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentSignaturesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentSignatures::route('/'),
            'create' => CreateDocumentSignature::route('/create'),
            'view' => ViewDocumentSignature::route('/{record}'),
            'edit' => EditDocumentSignature::route('/{record}/edit'),
        ];
    }
}
