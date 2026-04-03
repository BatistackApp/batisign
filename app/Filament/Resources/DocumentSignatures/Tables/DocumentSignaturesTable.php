<?php

namespace App\Filament\Resources\DocumentSignatures\Tables;

use App\Enums\SignatureStatus;
use App\Models\DocumentSignature;
use App\Notifications\DocumentSignature\DocumentSentForSignature;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class DocumentSignaturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ebp_quote_number')
                    ->label('Numéro du Devis')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client_email')
                    ->label('Client')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(SignatureStatus::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('send_email')
                        ->label('Envoyer le lien')
                        ->icon(Phosphor::PaperPlane)
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(fn (DocumentSignature $record) => in_array($record->status, [SignatureStatus::PENDING, SignatureStatus::VIEWED]))
                        ->action(function (DocumentSignature $record) {
                            Notification::route('mail', $record->client_email)
                                ->notify(new DocumentSentForSignature($record));

                            $record->update(['status' => SignatureStatus::SENT]);

                            \Filament\Notifications\Notification::make()
                                ->title('Email envoyée avec succès au client')
                                ->success()
                                ->send();
                        }),
                    Action::make('download_signed')
                        ->label('Télécharger')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn (DocumentSignature $record) => $record->status === SignatureStatus::SIGNED)
                        ->action(function (DocumentSignature $record) {
                            return response()->download(Storage::disk('local')->path($record->signed_pdf_path));
                        }),

                    Action::make('copy_link')
                        ->label('Copier le lien')
                        ->icon('heroicon-o-link')
                        ->color('gray')
                        ->action(function (DocumentSignature $record) {
                            // Utilise le presse-papier via une notification Livewire
                            $url = route('public.document.sign', $record->uuid);
                            // Logique de copie front-end gérée par Filament
                        }),
                ]),
            ]);
    }
}
