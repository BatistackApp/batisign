<?php

namespace App\Models;

use App\Enums\SignatureStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'ebp_quote_number',
        'original_pdf_path',
        'signed_pdf_path',
        'status',
        'signature_data',
        'signer_name',
        'signer_ip',
        'signed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'signed_at' => 'datetime',
            'expires_at' => 'datetime',
            'status' => SignatureStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
