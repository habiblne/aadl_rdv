@props([
    'actorLabel',
    'userIdentifier',
    'dashboardRoute',
    'logoutRoute',
    'navLinks' => [],
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Gestion des rendez-vous AADL</title>

        <x-theme-script />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-900 dark:text-slate-100">
        <div class="min-h-screen">
            <header class="border-b border-emerald-100 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route($dashboardRoute) }}" class="flex items-center gap-3">
                            <x-aadl-logo class="h-16 w-auto" />
                            <span>
                                <span class="block text-base font-bold text-slate-950 dark:text-white">Gestion des rendez-vous AADL</span>
                                <span class="block text-sm text-slate-600 dark:text-slate-300">{{ $actorLabel }} - {{ $userIdentifier }}</span>
                            </span>
                        </a>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                            <x-theme-toggle />

                            <form method="POST" action="{{ route($logoutRoute) }}">
                                @csrf
                                <x-secondary-button type="submit">Se déconnecter</x-secondary-button>
                            </form>
                        </div>
                    </div>

                    @if (! empty($navLinks))
                        <nav class="flex flex-wrap gap-2" aria-label="Navigation {{ $actorLabel }}">
                            @foreach ($navLinks as $link)
                                <a href="{{ $link['url'] }}" class="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:text-slate-200 dark:hover:bg-emerald-950 dark:hover:text-emerald-100 dark:focus:ring-offset-slate-900">
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </nav>
                    @endif
                </div>
            </header>

            <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
