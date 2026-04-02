<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::livewire('/sign/{uuid}', 'public-signature-portal')->name('public.document.sign');

require __DIR__.'/settings.php';
