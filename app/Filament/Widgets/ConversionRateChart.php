<?php

namespace App\Filament\Widgets;

use App\Enums\SignatureStatus;
use App\Models\DocumentSignature;
use Filament\Widgets\ChartWidget;

class ConversionRateChart extends ChartWidget
{
    protected ?string $heading = 'Taux de conversion des devis';
    protected static ?int $sort = 1;

    protected function getData(): array
    {
        // On récupère les compteurs pour chaque statut pertinent
        $signedCount = DocumentSignature::where('status', SignatureStatus::SIGNED)->count();
        $sentCount = DocumentSignature::where('status', SignatureStatus::SENT)->count();
        $expiredCount = DocumentSignature::where('status', SignatureStatus::EXPIRED)->count();

        return [
            'datasets' => [
                [
                    'label' => 'Nombre de devis',
                    'data' => [$signedCount, $sentCount, $expiredCount],
                    'backgroundColor' => [
                        '#10b981', // Émeraude (Succès/Signé)
                        '#f59e0b', // Ambre (En attente/Envoyé)
                        '#ef4444', // Rouge (Expiré)
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Signés', 'En attente', 'Expirés'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '70%', // Épaisseur de l'anneau
        ];
    }
}
