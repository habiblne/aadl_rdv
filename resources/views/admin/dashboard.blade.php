<x-aadl-layout
    actor-label="Admin"
    :user-identifier="$actor->email"
    dashboard-route="admin.dashboard"
    logout-route="admin.logout"
    :nav-links="[
        ['label' => 'Souscripteurs', 'url' => route('admin.souscripteurs.index')],
        ['label' => 'Responsables', 'url' => route('admin.responsables.index')],
        ['label' => 'Agents', 'url' => route('admin.agents.index')],
        ['label' => 'Administrateurs', 'url' => route('admin.admins.index')],
        ['label' => 'Tous les rendez-vous', 'url' => route('admin.rdvs.index')],
    ]"
>
    <x-page-title title="Tableau de bord Admin" subtitle="Espace administrateur AADL." />

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-action-card :href="route('admin.souscripteurs.index')" title="Souscripteurs" description="Consulter les souscripteurs inscrits." />
        <x-action-card :href="route('admin.responsables.index')" title="Responsables" description="Consulter les responsables par direction." />
        <x-action-card :href="route('admin.agents.index')" title="Agents" description="Consulter les agents par direction." />
        <x-action-card :href="route('admin.admins.index')" title="Administrateurs" description="Consulter les comptes administrateurs." />
        <x-action-card :href="route('admin.rdvs.index')" title="Tous les rendez-vous" description="Consulter tous les rendez-vous de l'application." />
    </section>
</x-aadl-layout>
