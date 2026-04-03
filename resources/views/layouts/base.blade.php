<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
        @filamentStyles
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="#" wire:navigate />
        </flux:header>

        {{ $slot }}

        @filamentScripts
        @fluxScripts
    </body>
</html>
