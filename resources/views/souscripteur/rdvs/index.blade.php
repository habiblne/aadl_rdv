<x-aadl-layout
    actor-label="Souscripteur"
    :user-identifier="Auth::guard('souscripteur')->user()->code"
    dashboard-route="souscripteur.dashboard"
    logout-route="souscripteur.logout"
    :nav-links="[
        ['label' => 'Mes informations', 'url' => route('souscripteur.profil')],
        ['label' => 'Prendre un rendez-vous', 'url' => route('souscripteur.rdvs.create')],
        ['label' => 'Mes rendez-vous', 'url' => route('souscripteur.rdvs.index')],
    ]"
>
    <x-page-title title="Mes rendez-vous" subtitle="Consultez l'état de vos rendez-vous et ouvrez votre fiche lorsque disponible." />

    <x-input-error :messages="$errors->get('rdv')" class="mb-4" />

    @if ($rdvs->isEmpty())
        <p class="rounded-lg border border-slate-200 bg-white p-5 text-sm text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
            Aucun rendez-vous trouvé.
        </p>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($rdvs as $rdv)
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ $rdv->dr->nom }}</h2>
                        <x-status-badge :status="$rdv->statut">{{ $rdv->statut_label }}</x-status-badge>
                    </div>
                    <dl class="divide-y divide-slate-100 dark:divide-slate-800">
                        <x-info-row label="Direction régionale">{{ $rdv->dr->nom }}</x-info-row>
                        <x-info-row label="Motif">{{ $rdv->motif }}</x-info-row>
                        <x-info-row label="Date">{{ $rdv->date->format('Y-m-d') }}</x-info-row>
                        <x-info-row label="Statut">{{ $rdv->statut_label }}</x-info-row>
                    </dl>

                    @php
                        $etapes = [
                            ['label' => 'Pris', 'statut' => \App\Models\Rdv::STATUT_RDV_PRIS],
                            ['label' => 'Accepté', 'statut' => \App\Models\Rdv::STATUT_RDV_ACCEPTE],
                            ['label' => 'Validé', 'statut' => \App\Models\Rdv::STATUT_RDV_VALIDE],
                            ['label' => 'Complété', 'statut' => \App\Models\Rdv::STATUT_RDV_COMPLETE],
                        ];
                    @endphp

                    <ol class="mt-5 grid grid-cols-4 gap-2" aria-label="Progression du rendez-vous">
                        @foreach ($etapes as $etape)
                            @php
                                $estCourante = $rdv->statut === $etape['statut'];
                                $estAtteinte = $rdv->statut >= $etape['statut'];
                            @endphp
                            <li class="relative">
                                @if (! $loop->last)
                                    <span class="absolute left-1/2 top-4 h-0.5 w-full {{ $rdv->statut > $etape['statut'] ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-slate-700' }}" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex flex-col items-center gap-2 text-center">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full border text-xs font-bold {{ $estAtteinte ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-slate-300 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' }} {{ $estCourante ? 'ring-2 ring-emerald-200 dark:ring-emerald-900' : '' }}">
                                        {{ $loop->iteration }}
                                    </span>
                                    <span class="text-[11px] font-semibold {{ $estAtteinte ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}">
                                        {{ $etape['label'] }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    @if ($rdv->statut === \App\Models\Rdv::STATUT_RDV_PRIS)
                        <p class="mt-4 rounded-md bg-amber-50 p-3 text-sm font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-100">En attente d'acceptation</p>
                    @else
                        <a href="{{ route('souscripteur.rdvs.fiche', $rdv->hashid) }}" class="mt-4 inline-flex items-center justify-center rounded-md bg-emerald-700 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                            Voir la fiche
                        </a>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $rdvs->links() }}
        </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('souscripteur.dashboard') }}" class="text-sm font-medium text-emerald-700 underline dark:text-emerald-400">
            Retour au tableau de bord
        </a>
    </div>
</x-aadl-layout>
