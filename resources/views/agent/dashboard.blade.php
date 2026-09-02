<x-aadl-layout
    actor-label="Agent"
    :user-identifier="$actor->email"
    dashboard-route="agent.dashboard"
    logout-route="agent.logout"
    :nav-links="[
        ['label' => 'Scanner un QR Code', 'url' => route('agent.scanner')],
    ]"
>
    <x-page-title title="Tableau de bord Agent" subtitle="Scannez les QR Codes de rendez-vous et ouvrez la vérification sécurisée." />

    <section class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <dl class="divide-y divide-slate-100">
            <x-info-row label="Type">Agent</x-info-row>
            <x-info-row label="Email">{{ $actor->email }}</x-info-row>
        </dl>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-action-card :href="route('agent.scanner')" title="Scanner un QR Code" description="Ouvrir la caméra pour scanner la fiche d'un rendez-vous." />
    </section>
</x-aadl-layout>
