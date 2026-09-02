<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>AADL - Gestion des Rendez-vous</title>

        <x-theme-script />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
        <div class="min-h-screen">
            <header class="border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                    <a href="{{ url('/') }}" class="flex items-center gap-3">
                        <x-aadl-logo class="h-20 w-auto" />
                        <span class="hidden text-sm font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200 sm:inline">Gestion des RDV</span>
                    </a>
                    <div class="flex items-center gap-2">
                        <a href="https://www.aadl.com.dz/" target="_blank" rel="noopener noreferrer" class="hidden text-sm font-medium text-slate-500 transition hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 sm:inline-flex">
                            Site officiel AADL
                            <svg class="ml-1 h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M7 17 17 7" />
                                <path d="M9 7h8v8" />
                            </svg>
                        </a>
                        <x-theme-toggle />
                        <a href="{{ route('souscripteur.login') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-500 hover:text-emerald-600 dark:border-slate-700 dark:text-slate-200 dark:hover:border-emerald-500 dark:hover:text-emerald-400">
                            Se connecter
                        </a>
                    </div>
                </div>
            </header>

            <main>
                <section class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                    <div class="mx-auto grid max-w-7xl gap-7 px-4 py-10 sm:px-6 sm:py-11 lg:grid-cols-[1.1fr_0.9fr] lg:gap-9 lg:px-8 lg:py-12">
                        <div class="flex flex-col justify-center">
                            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">AADL - Rendez-vous</p>
                            <h1 class="mt-2 max-w-3xl text-4xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-5xl">
                                Service de rendez-vous AADL.
                            </h1>
                            <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">
                                Prenez un rendez-vous, consultez son statut et accédez à votre fiche depuis votre espace Souscripteur.
                            </p>
                            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('souscripteur.rdvs.create') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                                    Prendre un rendez-vous
                                </a>
                                <a href="{{ route('souscripteur.login') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-emerald-500 hover:text-emerald-600 dark:border-slate-700 dark:text-slate-200 dark:hover:border-emerald-500 dark:hover:text-emerald-400">
                                    Se connecter
                                </a>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950 sm:p-5">
                            <div class="flex items-center gap-4 border-b border-slate-200 pb-3 dark:border-slate-800">
                                <x-aadl-logo class="h-20 w-auto" />
                                <div>
                                    <p class="text-sm font-semibold text-slate-950 dark:text-white">Espace Souscripteur</p>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Statuts du rendez-vous.</p>
                                </div>
                            </div>

                            <ol class="mt-4 space-y-0">
                                @foreach (['RDV pris', 'RDV accepté', 'RDV validé', 'RDV complété'] as $index => $label)
                                    <li class="relative flex gap-4 pb-4 last:pb-0">
                                        @if (! $loop->last)
                                            <span class="absolute left-4 top-8 h-full w-0.5 bg-slate-200 dark:bg-slate-700" aria-hidden="true"></span>
                                        @endif
                                        <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $index === 0 ? 'bg-emerald-700 text-white ring-4 ring-emerald-100 dark:ring-emerald-950' : 'bg-white text-slate-500 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700' }} text-sm font-bold">
                                            {{ $index + 1 }}
                                        </span>
                                        <div class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $label }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </section>

                <section id="comment-ca-marche" class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-11 lg:px-8">
                    <div class="max-w-2xl">
                        <h2 class="text-2xl font-bold text-slate-950 dark:text-white">Comment ça marche ?</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Quatre étapes simples.</p>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-4">
                        @foreach ([
                            ['num' => '01', 'titre' => 'Connexion', 'texte' => 'Accès à votre espace.'],
                            ['num' => '02', 'titre' => 'Demande', 'texte' => 'Choix de la direction et de la date.'],
                            ['num' => '03', 'titre' => 'Présence', 'texte' => 'Contrôle le jour du rendez-vous.'],
                            ['num' => '04', 'titre' => 'Suivi', 'texte' => 'Consultation du statut et de la fiche.'],
                        ] as $step)
                            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
                                <p class="text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ $step['num'] }}</p>
                                <h3 class="mt-3 font-semibold text-slate-950 dark:text-white">{{ $step['titre'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $step['texte'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="border-y border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-11 lg:px-8">
                        <div class="grid gap-7 lg:grid-cols-[0.95fr_1.05fr] lg:gap-8">
                            <div>
                                <h2 class="text-2xl font-bold text-slate-950 dark:text-white">Règles principales</h2>
                                <div class="mt-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                                    @foreach ([
                                        ['icone' => 'J+3', 'titre' => 'J+3', 'texte' => 'Réservation au moins 3 jours à l’avance.'],
                                        ['icone' => '1', 'titre' => '1 RDV actif', 'texte' => 'Un seul rendez-vous non complété.'],
                                        ['icone' => '30', 'titre' => '30 par jour', 'texte' => 'Maximum par direction et par date.'],
                                    ] as $rule)
                                        <div class="flex gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-700 text-xs font-bold text-white">{{ $rule['icone'] }}</span>
                                            <div>
                                                <h3 class="text-sm font-semibold text-slate-950 dark:text-white">{{ $rule['titre'] }}</h3>
                                                <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $rule['texte'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <h2 class="text-2xl font-bold text-slate-950 dark:text-white">FAQ</h2>
                                <div class="mt-4 space-y-3">
                                    @foreach ([
                                        ['q' => 'Quand puis-je réserver ?', 'r' => 'À partir de J+3.'],
                                        ['q' => 'Combien de RDV actifs ?', 'r' => 'Un seul par Souscripteur.'],
                                        ['q' => 'Date complète ?', 'r' => 'Choisissez une autre date disponible.'],
                                    ] as $faq)
                                        <details class="group rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm transition dark:border-slate-800 dark:bg-slate-950">
                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-slate-900 dark:text-white">
                                                <span>{{ $faq['q'] }}</span>
                                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-slate-200 text-emerald-700 transition group-open:rotate-45 dark:border-slate-700 dark:text-emerald-400">+</span>
                                            </summary>
                                            <p class="mt-3 border-t border-slate-200 pt-3 text-sm leading-6 text-slate-600 dark:border-slate-800 dark:text-slate-300">{{ $faq['r'] }}</p>
                                        </details>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="bg-slate-950 px-4 py-7 text-sm text-slate-300 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="font-semibold text-white">AADL – Gestion des rendez-vous</p>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-5">
                        <a href="https://www.aadl.com.dz/" target="_blank" rel="noopener noreferrer" class="font-medium text-slate-400 transition hover:text-emerald-300">
                            Site officiel AADL
                            <svg class="ml-1 inline h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M7 17 17 7" />
                                <path d="M9 7h8v8" />
                            </svg>
                        </a>
                        <a href="{{ route('souscripteur.login') }}" class="font-semibold text-emerald-400 transition hover:text-emerald-300">Accéder à mon espace</a>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
