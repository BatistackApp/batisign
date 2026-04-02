<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum SignatureStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case VIEWED = 'viewed';
    case SIGNED = 'signed';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::SENT => 'info',
            self::VIEWED => 'warning',
            self::SIGNED => 'success',
            self::REJECTED, self::EXPIRED => 'danger',
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::PENDING => 'En attente d\'envoi',
            self::SENT => 'Envoyé au client',
            self::VIEWED => 'Consulté',
            self::SIGNED => 'Signé',
            self::REJECTED => 'Refusé',
            self::EXPIRED => 'Expiré',
        };
    }
}
