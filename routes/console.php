<?php

use App\Enums\SignatureStatus;
use App\Models\DocumentSignature;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $expiredCount = DocumentSignature::whereIn('status', [SignatureStatus::PENDING, SignatureStatus::SENT, SignatureStatus::VIEWED])
        ->where('expires_at', '<', now())
        ->update(['status' => SignatureStatus::EXPIRED]);

    if ($expiredCount > 0) {
        Log::info("{$expiredCount} documents de signature ont expiré aujourd'hui.");
    }
})->dailyAt('02:00')->name('expire-signatures')->withoutOverlapping();
