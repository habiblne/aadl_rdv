<x-aadl-layout
    actor-label="Agent"
    :user-identifier="Auth::guard('agent')->user()->email"
    dashboard-route="agent.dashboard"
    logout-route="agent.logout"
    :nav-links="[
        ['label' => 'Scanner un QR Code', 'url' => route('agent.scanner')],
    ]"
>
    <x-page-title title="Vérification du rendez-vous" subtitle="Contrôlez les informations avant de valider la présence." />

    @if (session('status'))
        <div class="mb-6 rounded-md bg-emerald-50 p-4 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <x-input-error :messages="$errors->get('rdv')" class="mb-4" />

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <h2 class="mb-3 text-base font-semibold text-slate-950">Rendez-vous</h2>
                <dl class="divide-y divide-slate-100">
                    <x-info-row label="Direction">{{ $rdv->dr->nom }}</x-info-row>
                    <x-info-row label="Date">{{ $rdv->date->format('Y-m-d') }}</x-info-row>
                    <x-info-row label="Motif">{{ $rdv->motif }}</x-info-row>
                    <x-info-row label="Statut"><x-status-badge :status="$rdv->statut">{{ $rdv->statut_label }}</x-status-badge></x-info-row>
                </dl>
            </div>

            <div>
                <h2 class="mb-3 text-base font-semibold text-slate-950">Souscripteur</h2>
                <dl class="divide-y divide-slate-100">
                    <x-info-row label="Code souscripteur">{{ $rdv->souscripteur->code }}</x-info-row>
                    <x-info-row label="Nom souscripteur">{{ $rdv->souscripteur->nom }}</x-info-row>
                    <x-info-row label="Prénom souscripteur">{{ $rdv->souscripteur->prenom }}</x-info-row>
                    <x-info-row label="NIN souscripteur">{{ $rdv->souscripteur->nin }}</x-info-row>
                </dl>
            </div>
        </div>

        @if ($rdv->statut === \App\Models\Rdv::STATUT_RDV_ACCEPTE)
            <form method="POST" action="{{ route('agent.rdvs.valider', $rdv->hashid) }}" class="mt-6">
                @csrf
                @method('PATCH')
                <x-primary-button>Valider la présence</x-primary-button>
            </form>
        @endif
    </section>

    <div class="mt-6">
        <a href="{{ route('agent.dashboard') }}" class="text-sm font-medium text-emerald-700 underline">
            Retour au tableau de bord
        </a>
    </div>
</x-aadl-layout>
