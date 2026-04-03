<?php

namespace App\Filament\Widgets;

use App\Enums\SignatureStatus;
use App\Models\DocumentSIgnature;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingSignaturesTable extends TableWidget
{
    /**
     * L'ordre d'affichage sur le Dashboard (2 = en dessous du graphique).
     */
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => // On sélectionne uniquement les devis en attente,
                // triés du plus ancien au plus récent.
            DocumentSIgnature::query()
                ->where('status', SignatureStatus::SENT)
                ->orderBy('created_at', 'asc'))
            ->heading('Devis en attente de signature (Les plus anciens)')
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Envoyé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    // On ajoute une description qui affiche le nombre de jours écoulés
                    ->description(fn(DocumentSIgnature $record): string => $record->created_at->diffForHumans()),

                TextColumn::make('amount')
                    ->label('Montant')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('reminded_at')
                    ->label('Dernière relance')
                    ->dateTime('d/m/Y')
                    ->placeholder('Jamais'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Voir le devis')
                    ->icon('heroicon-m-eye')
                    // Remplacez 'DocumentSignatureResource' par le nom exact de votre ressource
                    ->url(fn(DocumentSIgnature $record): string => route('filament.admin.resources.document-signatures.view', $record)),
            ]);
    }
}
