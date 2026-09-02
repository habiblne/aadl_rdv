<x-aadl-layout
    actor-label="Admin"
    :user-identifier="Auth::guard('admin')->user()->email"
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
    <x-page-title title="Tous les rendez-vous" subtitle="Consultation globale des rendez-vous de toutes les directions." />

    <form method="GET" action="{{ route('admin.rdvs.index') }}" class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <div class="grid gap-4 md:grid-cols-4 md:items-end">
            <div>
                <x-input-label for="date" value="Date" />
                <x-text-input id="date" name="date" class="mt-1 block w-full" type="date" :value="$filters['date']" />
                <x-input-error :messages="$errors->get('date')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="dr_id" value="Direction" />
                <select id="dr_id" name="dr_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">Toutes les directions</option>
                    @foreach ($drs as $dr)
                        <option value="{{ $dr->id }}" @selected((string) $filters['dr_id'] === (string) $dr->id)>{{ $dr->nom }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('dr_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="statut" value="Statut" />
                <select id="statut" name="statut" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">Tous les statuts</option>
                    <option value="0" @selected((string) $filters['statut'] === '0')>RDV pris</option>
                    <option value="1" @selected((string) $filters['statut'] === '1')>RDV accepté</option>
                    <option value="2" @selected((string) $filters['statut'] === '2')>RDV validé</option>
                    <option value="3" @selected((string) $filters['statut'] === '3')>RDV complété</option>
                </select>
                <x-input-error :messages="$errors->get('statut')" class="mt-2" />
            </div>
            <x-primary-button>Filtrer</x-primary-button>
        </div>
    </form>

    @if ($rdvs->isEmpty())
        <p class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">Aucun rendez-vous trouvé.</p>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="hidden grid-cols-[1fr_1fr_1fr_1fr_1.2fr] gap-4 border-b border-slate-200 bg-slate-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400 lg:grid">
                <span>Rendez-vous</span>
                <span>Direction</span>
                <span>Statut</span>
                <span>Souscripteur</span>
                <span>Traçabilité</span>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @foreach ($rdvs as $rdv)
                    <article class="grid gap-4 px-4 py-5 text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-950 sm:px-5 lg:grid-cols-[1fr_1fr_1fr_1fr_1.2fr]">
                        <div>
                            <p class="font-semibold text-slate-950 dark:text-white">{{ $rdv->date->format('Y-m-d') }}</p>
                            <p class="mt-1 leading-6 text-slate-600 dark:text-slate-300">{{ $rdv->motif }}</p>
                        </div>
                        <p><span class="font-semibold text-slate-500 dark:text-slate-400 lg:hidden">Direction: </span>{{ $rdv->dr->nom }}</p>
                        <div>
                            <p class="sr-only">Statut: {{ $rdv->statut_label }}</p>
                            <x-status-badge :status="$rdv->statut">{{ $rdv->statut_label }}</x-status-badge>
                        </div>
                        <div class="space-y-1">
                            <p><span class="font-semibold text-slate-950 dark:text-white">Code:</span> {{ $rdv->souscripteur->code }}</p>
                            <p><span class="font-semibold text-slate-950 dark:text-white">Nom:</span> {{ $rdv->souscripteur->nom }}</p>
                            <p><span class="font-semibold text-slate-950 dark:text-white">Prénom:</span> {{ $rdv->souscripteur->prenom }}</p>
                            <p class="break-all"><span class="font-semibold text-slate-950 dark:text-white">NIN:</span> {{ $rdv->souscripteur->nin }}</p>
                        </div>
                        <div class="space-y-3">
                            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950">
                                <p class="font-semibold text-slate-950 dark:text-white">Acceptation</p>
                                @if ($rdv->accepted_at)
                                    <p>Accepté par: {{ $rdv->acceptedByResponsable?->email ?? 'Responsable supprimé' }}</p>
                                    <p>Date et heure d’acceptation: {{ $rdv->accepted_at->format('Y-m-d H:i') }}</p>
                                @else
                                    <p>Non accepté</p>
                                @endif
                            </div>

                            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950">
                                <p class="font-semibold text-slate-950 dark:text-white">Validation</p>
                                @if ($rdv->validated_at)
                                    <p>Validé par: {{ $rdv->validatedByAgent?->email ?? 'Agent supprimé' }}</p>
                                    <p>Date et heure de validation: {{ $rdv->validated_at->format('Y-m-d H:i') }}</p>
                                @else
                                    <p>Non validé</p>
                                @endif
                            </div>

                            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-950">
                                <p class="font-semibold text-slate-950 dark:text-white">Complétion</p>
                                @if ($rdv->completed_at)
                                    <p>Complété par: {{ $rdv->completedByResponsable?->email ?? 'Responsable supprimé' }}</p>
                                    <p>Date et heure de complétion: {{ $rdv->completed_at->format('Y-m-d H:i') }}</p>
                                @else
                                    <p>Non complété</p>
                                @endif
                            </div>
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
        <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-emerald-700 underline dark:text-emerald-400">Retour au tableau de bord</a>
    </div>
</x-aadl-layout>
