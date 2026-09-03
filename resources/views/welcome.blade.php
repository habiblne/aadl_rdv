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
        <div class="min-h-screen overflow-hidden">
            <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-slate-950/80 text-white shadow-lg shadow-slate-950/10 backdrop-blur-xl">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                    <a href="{{ url('/') }}" class="flex items-center gap-3">
                        <x-aadl-logo class="h-20 w-auto sm:h-24" />
                        <div class="hidden sm:block">
                            <p class="text-base font-bold uppercase tracking-wide text-white">AADL</p>
                            <p class="text-sm font-medium text-slate-300">Gestion des RDV</p>
                        </div>
                    </a>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <a href="https://www.aadl.com.dz/" target="_blank" rel="noopener noreferrer" class="hidden items-center rounded-full px-3 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10 hover:text-emerald-300 sm:inline-flex">
                            Site officiel AADL
                            <svg class="ml-1.5 h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M7 17 17 7" />
                                <path d="M9 7h8v8" />
                            </svg>
                        </a>
                        <x-theme-toggle />
                        <a href="{{ route('souscripteur.login') }}" class="inline-flex items-center rounded-full border border-emerald-400/40 bg-white px-4 py-2 text-sm font-bold text-emerald-800 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50">
                            Se connecter
                        </a>
                    </div>
                </div>
            </header>

            <main class="pt-[104px] sm:pt-[120px]">
                <section class="relative bg-slate-950 text-white">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_10%,rgba(16,185,129,0.22),transparent_28%),linear-gradient(135deg,#071426_0%,#0f2f4b_48%,#064e3b_100%)]"></div>
                    <div class="relative mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 sm:py-14 lg:grid-cols-[0.95fr_1.05fr] lg:items-center lg:px-8 lg:py-16">
                        <div class="relative z-10 max-w-2xl">
                            <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-300">AADL - Rendez-vous</p>
                            <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                                Service de <span class="whitespace-nowrap">rendez-vous</span> AADL.
                            </h1>
                            <p class="mt-5 max-w-xl text-base leading-8 text-slate-200 sm:text-lg">
                                Prenez un rendez-vous, consultez son statut et accédez à votre fiche depuis votre espace Souscripteur.
                            </p>
                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('souscripteur.rdvs.create') }}" class="inline-flex items-center justify-center rounded-full bg-emerald-500 px-6 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-950/30 transition hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2 focus:ring-offset-slate-950">
                                    Prendre un rendez-vous
                                </a>
                                <a href="{{ route('souscripteur.login') }}" class="inline-flex items-center justify-center rounded-full border border-white/25 bg-white/10 px-6 py-3 text-sm font-bold text-white backdrop-blur transition hover:border-emerald-300 hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2 focus:ring-offset-slate-950">
                                    Se connecter
                                </a>
                            </div>
                        </div>

                        <div class="relative min-h-[320px] overflow-hidden rounded-[2rem] border border-white/10 bg-slate-900 shadow-2xl shadow-slate-950/50 sm:min-h-[420px] lg:min-h-[500px]">
                            <img src="{{ asset('images/aadl-headquarters.jpg') }}" alt="Nouveau siège AADL" class="absolute inset-0 h-full w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/70 via-slate-950/10 to-transparent lg:bg-gradient-to-l lg:from-slate-950/10 lg:via-slate-950/5 lg:to-slate-950/55"></div>
                            <div class="absolute -left-20 top-0 hidden h-full w-48 rounded-r-[100%] bg-slate-950/55 blur-sm lg:block"></div>
                        </div>
                    </div>

                    <div class="relative mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
                        <div class="rounded-2xl border border-white/10 bg-white/10 p-4 shadow-xl shadow-slate-950/20 backdrop-blur md:p-5">
                            <div class="grid gap-3 md:grid-cols-4">
                                @foreach (['RDV pris', 'RDV accepté', 'RDV validé', 'RDV complété'] as $index => $label)
                                    <div class="relative rounded-xl border border-white/15 bg-white/15 p-4">
                                        @if (! $loop->last)
                                            <span class="absolute left-[calc(100%-0.25rem)] top-1/2 hidden h-px w-4 bg-emerald-300/50 md:block" aria-hidden="true"></span>
                                        @endif
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $index === 0 ? 'bg-emerald-400 text-slate-950' : 'bg-white/25 text-white ring-1 ring-white/35' }} text-sm font-bold">
                                                {{ $index + 1 }}
                                            </span>
                                            <p class="text-sm font-bold text-white">{{ $label }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <section id="comment-ca-marche" class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-14 lg:px-8">
                    <div class="max-w-2xl">
                        <p class="text-sm font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Parcours souscripteur</p>
                        <h2 class="mt-2 text-3xl font-bold text-slate-950 dark:text-white">Comment ça marche ?</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Quatre étapes simples.</p>
                    </div>

                    <div class="mt-7 grid gap-4 md:grid-cols-4">
                        @foreach ([
                            ['num' => '01', 'titre' => 'Connexion', 'texte' => 'Accès à votre espace.'],
                            ['num' => '02', 'titre' => 'Demande', 'texte' => 'Choix de la direction et de la date.'],
                            ['num' => '03', 'titre' => 'Présence', 'texte' => 'Contrôle le jour du rendez-vous.'],
                            ['num' => '04', 'titre' => 'Suivi', 'texte' => 'Consultation du statut et de la fiche.'],
                        ] as $step)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg hover:shadow-slate-200/70 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-900 dark:hover:shadow-slate-950/40">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-900">
                                        @if ($loop->index === 0)
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                                                <path d="m10 17 5-5-5-5" />
                                                <path d="M15 12H3" />
                                            </svg>
                                        @elseif ($loop->index === 1)
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M8 2v4" />
                                                <path d="M16 2v4" />
                                                <rect width="18" height="18" x="3" y="4" rx="2" />
                                                <path d="M3 10h18" />
                                            </svg>
                                        @elseif ($loop->index === 2)
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M20 6 9 17l-5-5" />
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                                                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                                                <path d="M8 13h8" />
                                                <path d="M8 17h5" />
                                            </svg>
                                        @endif
                                    </div>
                                    <span class="text-sm font-black text-emerald-700 dark:text-emerald-300">{{ $step['num'] }}</span>
                                </div>
                                <h3 class="mt-5 font-bold text-slate-950 dark:text-white">{{ $step['titre'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $step['texte'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="border-y border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-14 lg:px-8">
                        <div class="max-w-2xl">
                            <p class="text-sm font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Cadre RDV</p>
                            <h2 class="mt-2 text-3xl font-bold text-slate-950 dark:text-white">Règles principales</h2>
                        </div>

                        <div class="mt-7 grid gap-4 md:grid-cols-3">
                            @foreach ([
                                ['icone' => 'J+3', 'titre' => 'J+3', 'texte' => 'Réservation au moins 3 jours à l’avance.'],
                                ['icone' => '1', 'titre' => '1 RDV actif', 'texte' => 'Un seul rendez-vous non complété.'],
                                ['icone' => '30', 'titre' => '30 par jour', 'texte' => 'Maximum par direction et par date.'],
                            ] as $rule)
                                <div class="flex gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-950 text-xs font-black text-emerald-300 dark:bg-emerald-500 dark:text-slate-950">{{ $rule['icone'] }}</span>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-950 dark:text-white">{{ $rule['titre'] }}</h3>
                                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $rule['texte'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </main>

            <footer class="bg-slate-950 px-4 py-6 text-sm text-slate-400 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <p class="font-semibold text-white">AADL - Gestion des rendez-vous</p>
                </div>
            </footer>
        </div>
    </body>
</html>
