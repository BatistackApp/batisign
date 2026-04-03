<?php

namespace App\Filament\Widgets;

use App\Enums\SignatureStatus;
use App\Models\DocumentSignature;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SignatureStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        // 1. Calcul du CA en attente
        $pendingRevenue = DocumentSignature::where('status', SignatureStatus::SENT)
            ->sum('amount');

        // 2. Calcul du délai moyen de signature
        // On récupère les documents signés pour calculer la différence entre l'envoi et la signature
        $signedDocs = DocumentSignature::where('status', SignatureStatus::SIGNED)
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->get();

        $averageHours = 0;
        if ($signedDocs->isNotEmpty()) {
            $totalHours = $signedDocs->sum(function ($doc) {
                return $doc->created_at->diffInHours($doc->updated_at);
            });
            $averageHours = $totalHours / $signedDocs->count();
        }

        // Formatage du délai pour un affichage lisible
        if ($averageHours > 24) {
            $days = round($averageHours / 24);
            $delayLabel = "{$days} jour(s)";
        } else {
            $delayLabel = round($averageHours).' heure(s)';
        }

        return [
            Stat::make('CA en attente', number_format($pendingRevenue, 2, ',', ' ').' €')
                ->description('Somme des montants des devis en cours')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Délai moyen', $delayLabel)
                ->description('Temps moyen avant signature client')
                ->descriptionIcon('heroicon-m-clock')
                ->color('success'),
        ];
    }
}
