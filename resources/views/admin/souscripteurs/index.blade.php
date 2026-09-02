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
    <x-page-title title="Liste des souscripteurs" subtitle="Consultation des souscripteurs enregistrés." />

    <form method="GET" action="{{ route('admin.souscripteurs.index') }}" class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
            <div>
                <x-input-label for="q" value="Recherche" />
                <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$query" placeholder="Code, nom, prénom ou NIN" />
            </div>
            <x-primary-button>Rechercher</x-primary-button>
        </div>
    </form>

    @if ($souscripteurs->isEmpty())
        <p class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">Aucun souscripteur trouvé.</p>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="hidden grid-cols-6 gap-4 border-b border-slate-200 bg-slate-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400 lg:grid">
                <span>Code</span>
                <span>Nom</span>
                <span>Prénom</span>
                <span>NIN</span>
                <span>Prop</span>
                <span>Wilaya</span>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @foreach ($souscripteurs as $souscripteur)
                    <article class="grid gap-3 px-4 py-4 text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-950 sm:px-5 lg:grid-cols-6 lg:items-center lg:gap-4">
                        <p class="font-medium text-slate-950 dark:text-white"><span class="font-semibold text-slate-500 dark:text-slate-400 lg:hidden">Code: </span>{{ $souscripteur->code }}</p>
                        <p><span class="font-semibold text-slate-500 dark:text-slate-400 lg:hidden">Nom: </span>{{ $souscripteur->nom }}</p>
                        <p><span class="font-semibold text-slate-500 dark:text-slate-400 lg:hidden">Prénom: </span>{{ $souscripteur->prenom }}</p>
                        <p class="break-all"><span class="font-semibold text-slate-500 dark:text-slate-400 lg:hidden">NIN: </span>{{ $souscripteur->nin }}</p>
                        <p><span class="font-semibold text-slate-500 dark:text-slate-400 lg:hidden">Prop: </span>{{ $souscripteur->prop }}</p>
                        <p><span class="font-semibold text-slate-500 dark:text-slate-400 lg:hidden">Wilaya: </span>{{ $souscripteur->wil }}</p>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="mt-6">
            {{ $souscripteurs->links() }}
        </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-emerald-700 underline dark:text-emerald-400">Retour au tableau de bord</a>
    </div>
</x-aadl-layout>
