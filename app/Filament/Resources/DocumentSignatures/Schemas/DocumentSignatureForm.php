<?php

namespace App\Filament\Resources\DocumentSignatures\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentSignatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Information du devis')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('ebp_quote_number')
                            ->label('Numéro de devis')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('client_email')
                            ->label('Email du client')
                            ->email()
                            ->required(),

                        FileUpload::make('original_pdf_path')
                            ->label('Document PDF')
                            ->directory('quotes/originals')
                            ->disk('local')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required()
                            ->preserveFilenames(),
                    ])
                    ->columns(2),
            ]);
    }
}
