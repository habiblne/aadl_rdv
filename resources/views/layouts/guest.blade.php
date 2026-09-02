<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Gestion des rendez-vous AADL</title>

        <x-theme-script />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-900 dark:text-slate-100">
        <div class="flex min-h-screen flex-col items-center px-4 py-8 sm:justify-center">
            <div class="flex flex-col items-center gap-4">
                <a href="/" class="inline-flex items-center gap-3">
                    <x-aadl-logo class="h-28 w-auto" />
                    <span>
                        <span class="block text-left text-base font-bold text-slate-950 dark:text-white">Gestion des rendez-vous AADL</span>
                        <span class="block text-left text-sm text-slate-600 dark:text-slate-300">Plateforme de rendez-vous</span>
                    </span>
                </a>
                <x-theme-toggle />
            </div>

            <div class="mt-6 w-full max-w-md overflow-hidden rounded-lg border border-slate-200 bg-white px-6 py-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
