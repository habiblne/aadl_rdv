<x-aadl-layout
    actor-label="Souscripteur"
    :user-identifier="$actor->code"
    dashboard-route="souscripteur.dashboard"
    logout-route="souscripteur.logout"
    :nav-links="[
        ['label' => 'Mes informations', 'url' => route('souscripteur.profil')],
        ['label' => 'Prendre un rendez-vous', 'url' => route('souscripteur.rdvs.create')],
        ['label' => 'Mes rendez-vous', 'url' => route('souscripteur.rdvs.index')],
    ]"
>
    @php
        $rdvActif = $actor->rdvs()
            ->with('dr')
            ->whereIn('statut', [
                \App\Models\Rdv::STATUT_RDV_PRIS,
                \App\Models\Rdv::STATUT_RDV_ACCEPTE,
                \App\Models\Rdv::STATUT_RDV_VALIDE,
            ])
            ->orderBy('date')
            ->first();
    @endphp

    <x-page-title title="Tableau de bord Souscripteur" subtitle="Consultez vos informations et gérez vos rendez-vous AADL." />

    @if (session('status'))
        <div class="mb-6 rounded-md bg-emerald-50 p-4 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-900">
            {{ session('status') }}
        </div>
    @endif

    <section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <dl class="divide-y divide-slate-100 dark:divide-slate-800">
            <x-info-row label="Type">Souscripteur</x-info-row>
            <x-info-row label="Code">{{ $actor->code }}</x-info-row>
            <x-info-row label="Nom">{{ $actor->nom }}</x-info-row>
            <x-info-row label="Prénom">{{ $actor->prenom }}</x-info-row>
        </dl>
    </section>

    <section class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @if ($rdvActif)
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Rendez-vous en cours</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ $rdvActif->dr->nom }}</h2>
                    <div class="mt-4 grid gap-3 text-sm text-slate-700 dark:text-slate-300 sm:grid-cols-3">
                        <div>
                            <span class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Date</span>
                            {{ $rdvActif->date->format('Y-m-d') }}
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Statut</span>
                            {{ $rdvActif->statut_label }}
                        </div>
                        <div>
                            <span class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Motif</span>
                            {{ $rdvActif->motif }}
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row lg:flex-col">
                    <a href="{{ route('souscripteur.rdvs.index') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                        Voir mes rendez-vous
                    </a>
                    @if ($rdvActif->statut !== \App\Models\Rdv::STATUT_RDV_PRIS)
                        <a href="{{ route('souscripteur.rdvs.fiche', $rdvActif->hashid) }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-500 hover:text-emerald-600 dark:border-slate-700 dark:text-slate-200 dark:hover:border-emerald-500 dark:hover:text-emerald-400">
                            Voir la fiche
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Aucun rendez-vous actif</p>
                    <h2 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">Vous pouvez prendre un nouveau rendez-vous.</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Choisissez une direction, un motif et une date disponible.</p>
                </div>
                <a href="{{ route('souscripteur.rdvs.create') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    Prendre un rendez-vous
                </a>
            </div>
        @endif
    </section>

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-action-card :href="route('souscripteur.profil')" title="Mes informations" description="Voir vos informations personnelles enregistrées." />
        <x-action-card :href="route('souscripteur.rdvs.create')" title="Prendre un rendez-vous" description="Créer une nouvelle demande de rendez-vous." />
        <x-action-card :href="route('souscripteur.rdvs.index')" title="Mes rendez-vous" description="Suivre vos rendez-vous et accéder à votre fiche." />
    </section>
</x-aadl-layout>
