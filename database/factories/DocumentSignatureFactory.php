<?php

namespace Database\Factories;

use App\Enums\SignatureStatus;
use App\Models\DocumentSignature;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class DocumentSignatureFactory extends Factory
{
    protected $model = DocumentSignature::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'ebp_quote_number' => 'DEV-'.date('Y').'-'.$this->faker->unique()->randomNumber(4, true),
            'original_pdf_path' => 'quotes/originals/fake_quote_'.Str::random(5).'.pdf',
            'signed_pdf_path' => null,
            'status' => SignatureStatus::PENDING,
            'signature_data' => null,
            'signer_name' => null,
            'signer_ip' => null,
            'signed_at' => null,
            'expires_at' => now()->addDays(30),
        ];
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SignatureStatus::SIGNED,
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', // Faux Base64 d'un pixel transparent
            'signer_name' => $this->faker->name(),
            'signer_ip' => $this->faker->ipv4(),
            'signed_at' => now(),
            'signed_pdf_path' => 'quotes/signed/fake_quote_signed_'.Str::random(5).'.pdf',
        ]);
    }
}
