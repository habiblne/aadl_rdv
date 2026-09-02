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
    <x-page-title title="Liste des agents" subtitle="Consultation des agents par direction." />

    @if (session('status'))
        <div class="mb-5 rounded-md bg-emerald-50 p-4 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-900">
            {{ session('status') }}
        </div>
    @endif

    <x-input-error :messages="$errors->get('agent')" class="mb-4" />

    <div class="mb-5 flex justify-start">
        <a href="{{ route('admin.agents.create') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-700 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
            Ajouter un agent
        </a>
    </div>

    <form method="GET" action="{{ route('admin.agents.index') }}" class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
            <div>
                <x-input-label for="q" value="Recherche par email" />
                <x-text-input id="q" name="q" class="mt-1 block w-full" :value="$query" placeholder="agent@aadl.test" />
            </div>
            <x-primary-button>Rechercher</x-primary-button>
        </div>
    </form>

    @if ($agents->isEmpty())
        <p class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">Aucun agent trouvé.</p>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="hidden grid-cols-[1fr_1fr_1fr_auto] gap-4 border-b border-slate-200 bg-slate-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400 lg:grid">
                <span>Email</span>
                <span>Direction</span>
                <span>Date de création</span>
                <span class="text-right">Action</span>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @foreach ($agents as $agent)
                    <article class="grid gap-4 px-4 py-4 text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-950 sm:px-5 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-center">
                        <p class="font-medium text-slate-950 dark:text-white"><span class="font-semibold text-slate-500 dark:text-slate-400 lg:hidden">Email: </span>{{ $agent->email }}</p>
                        <p><span class="font-semibold text-slate-500 dark:text-slate-400 lg:hidden">Direction: </span>{{ $agent->dr->nom }}</p>
                        <p><span class="font-semibold text-slate-500 dark:text-slate-400 lg:hidden">Date de création: </span>{{ $agent->created_at->format('Y-m-d') }}</p>
                        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap lg:justify-end">
                            <a href="{{ route('admin.agents.edit', $agent) }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-offset-slate-900">
                                Modifier
                            </a>
                            <a href="{{ route('admin.agents.password.edit', $agent) }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-offset-slate-900">
                                Réinitialiser le mot de passe
                            </a>
                            <form method="POST" action="{{ route('admin.agents.destroy', $agent) }}" onsubmit="return confirm('Confirmer la suppression de ce compte ?')">
                                @csrf
                                @method('DELETE')
                                <x-danger-button>Supprimer</x-danger-button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="mt-6">
            {{ $agents->links() }}
        </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-emerald-700 underline dark:text-emerald-400">Retour au tableau de bord</a>
    </div>
</x-aadl-layout>
