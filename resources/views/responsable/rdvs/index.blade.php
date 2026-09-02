<x-aadl-layout
    actor-label="Responsable"
    :user-identifier="Auth::guard('responsable')->user()->email"
    dashboard-route="responsable.dashboard"
    logout-route="responsable.logout"
    :nav-links="[
        ['label' => 'Liste des rendez-vous', 'url' => route('responsable.rdvs.index')],
    ]"
>
    <x-page-title title="Liste des rendez-vous" subtitle="Rendez-vous associés à votre direction, avec filtre par date." />

    @if (session('status'))
        <div class="mb-6 rounded-md bg-emerald-50 p-4 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <x-input-error :messages="$errors->get('rdv')" class="mb-4" />

    <form method="GET" action="{{ route('responsable.rdvs.index') }}" class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
            <div>
                <x-input-label for="date" value="Filtrer par date" />
                <x-text-input id="date" class="mt-1 block w-full" type="date" name="date" :value="$selectedDate" />
                <x-input-error :messages="$errors->get('date')" class="mt-2" />
            </div>

            <x-primary-button>Filtrer</x-primary-button>
        </div>
    </form>

    @if ($rdvs->isEmpty())
        <p class="rounded-lg border border-slate-200 bg-white p-5 text-sm text-slate-700 shadow-sm">
            Aucun rendez-vous trouvé pour votre direction régionale.
        </p>
    @else
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="hidden grid-cols-[1fr_1fr_1fr_auto] gap-4 border-b border-slate-200 bg-slate-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600 lg:grid">
                <span>Rendez-vous</span>
                <span>Souscripteur</span>
                <span>Statut</span>
                <span>Action</span>
            </div>

            <div class="divide-y divide-slate-200">
                @foreach ($rdvs as $rdv)
                    <article class="grid gap-4 px-5 py-4 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-center">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $rdv->date->format('Y-m-d') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $rdv->motif }}</p>
                        </div>

                        <div class="text-sm text-slate-700">
                            <p><span class="font-semibold">Code souscripteur:</span> {{ $rdv->souscripteur->code }}</p>
                            <p><span class="font-semibold">Nom souscripteur:</span> {{ $rdv->souscripteur->nom }}</p>
                            <p><span class="font-semibold">Prénom souscripteur:</span> {{ $rdv->souscripteur->prenom }}</p>
                            <p><span class="font-semibold">NIN souscripteur:</span> {{ $rdv->souscripteur->nin }}</p>
                        </div>

                        <div>
                            <p class="sr-only">Statut: {{ $rdv->statut_label }}</p>
                            <x-status-badge :status="$rdv->statut">{{ $rdv->statut_label }}</x-status-badge>
                        </div>

                        <div>
                            @if ($rdv->statut === \App\Models\Rdv::STATUT_RDV_PRIS)
                                <form method="POST" action="{{ route('responsable.rdvs.accepter', $rdv) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-primary-button>Accepter</x-primary-button>
                                </form>
                            @elseif ($rdv->statut === \App\Models\Rdv::STATUT_RDV_VALIDE)
                                <form method="POST" action="{{ route('responsable.rdvs.completer', $rdv) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-primary-button>Compléter</x-primary-button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="mt-6">
            {{ $rdvs->links() }}
        </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('responsable.dashboard') }}" class="text-sm font-medium text-emerald-700 underline">
            Retour au tableau de bord
        </a>
    </div>
</x-aadl-layout>
