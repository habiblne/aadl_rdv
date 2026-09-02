<x-aadl-layout
    actor-label="Responsable"
    :user-identifier="$actor->email"
    dashboard-route="responsable.dashboard"
    logout-route="responsable.logout"
    :nav-links="[
        ['label' => 'Liste des rendez-vous', 'url' => route('responsable.rdvs.index')],
    ]"
>
    <x-page-title title="Tableau de bord Responsable" subtitle="Suivez les rendez-vous liés à votre direction." />

    <section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <dl class="divide-y divide-slate-100">
            <x-info-row label="Type">Responsable</x-info-row>
            <x-info-row label="Email">{{ $actor->email }}</x-info-row>
        </dl>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-action-card :href="route('responsable.rdvs.index')" title="Liste des rendez-vous" description="Filtrer, accepter et compléter les rendez-vous de votre direction." />
    </section>
</x-aadl-layout>
