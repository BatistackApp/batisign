<?php

use App\Enums\SignatureStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('client_email');
            $table->string('ebp_quote_number');
            $table->string('original_pdf_path');
            $table->string('signed_pdf_path')->nullable();
            $table->string('status')->default(SignatureStatus::PENDING->value);
            $table->longText('signature_data')->nullable();
            $table->string('signer_name')->nullable();
            $table->ipAddress('signer_ip')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['uuid']);
            $table->index(['ebp_quote_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
    }
};
