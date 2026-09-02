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
    <x-page-title title="Mes informations" subtitle="Informations enregistrées pour votre compte souscripteur." />

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <dl class="divide-y divide-slate-100">
            <x-info-row label="Code">{{ $souscripteur->code }}</x-info-row>
            <x-info-row label="Nom">{{ $souscripteur->nom }}</x-info-row>
            <x-info-row label="Prénom">{{ $souscripteur->prenom }}</x-info-row>
            <x-info-row label="NIN">{{ $souscripteur->nin }}</x-info-row>
            <x-info-row label="Prop">{{ $souscripteur->prop }}</x-info-row>
            <x-info-row label="Wilaya">{{ $souscripteur->wil }}</x-info-row>
        </dl>
    </section>

    <div class="mt-6">
        <a href="{{ route('souscripteur.dashboard') }}" class="text-sm font-medium text-emerald-700 underline">
            Retour au tableau de bord
        </a>
    </div>
</x-aadl-layout>
