<x-aadl-layout
    actor-label="Souscripteur"
    :user-identifier="$souscripteur->code"
    dashboard-route="souscripteur.dashboard"
    logout-route="souscripteur.logout"
    :nav-links="[
        ['label' => 'Mes informations', 'url' => route('souscripteur.profil')],
        ['label' => 'Prendre un rendez-vous', 'url' => route('souscripteur.rdvs.create')],
        ['label' => 'Mes rendez-vous', 'url' => route('souscripteur.rdvs.index')],
    ]"
>
    <x-page-title title="Fiche de rendez-vous" subtitle="Présentez cette fiche le jour du rendez-vous. Le QR Code ne contient aucune donnée personnelle." />

    <section class="print-light mx-auto max-w-3xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm print:shadow-none">
        <div class="border-b border-slate-200 pb-4">
            <x-aadl-logo class="h-20 w-auto" />
            <h2 class="mt-1 text-xl font-bold text-slate-950">Fiche de rendez-vous</h2>
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
            <div>
                <h3 class="mb-3 text-base font-semibold text-slate-950">Souscripteur</h3>
                <dl class="divide-y divide-slate-100">
                    <x-info-row label="Code">{{ $souscripteur->code }}</x-info-row>
                    <x-info-row label="Nom">{{ $souscripteur->nom }}</x-info-row>
                    <x-info-row label="Prénom">{{ $souscripteur->prenom }}</x-info-row>
                    <x-info-row label="NIN">{{ $souscripteur->nin }}</x-info-row>
                </dl>
            </div>

            <div>
                <h3 class="mb-3 text-base font-semibold text-slate-950">Rendez-vous</h3>
                <dl class="divide-y divide-slate-100">
                    <x-info-row label="Direction">{{ $rdv->dr->nom }}</x-info-row>
                    <x-info-row label="Motif">{{ $rdv->motif }}</x-info-row>
                    <x-info-row label="Date">{{ $rdv->date->format('Y-m-d') }}</x-info-row>
                    <x-info-row label="Statut"><x-status-badge :status="$rdv->statut">{{ $rdv->statut_label }}</x-status-badge></x-info-row>
                </dl>
            </div>
        </div>

        <div class="mt-8 text-center" data-qr-content="{{ $verificationUrl }}">
            <h3 class="mb-3 text-base font-semibold text-slate-950">QR Code</h3>
            <div class="inline-block rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                {!! $qrCode !!}
            </div>
        </div>
    </section>

    <div class="mt-6">
        <a href="{{ route('souscripteur.rdvs.index') }}" class="text-sm font-medium text-emerald-700 underline">
            Retour à Mes rendez-vous
        </a>
    </div>
</x-aadl-layout>
