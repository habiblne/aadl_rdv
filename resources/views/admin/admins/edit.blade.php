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
    <x-page-title title="Modifier l’administrateur" subtitle="Mettre à jour l'email du compte administrateur." />

    <form method="POST" action="{{ route('admin.admins.update', $admin) }}" class="max-w-3xl space-y-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        @csrf
        @method('PATCH')

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" class="mt-1 block w-full" type="email" :value="old('email', $admin->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.admins.index') }}" class="text-sm font-medium text-emerald-700 underline">Retour à la liste</a>
            <x-primary-button>Enregistrer</x-primary-button>
        </div>
    </form>
</x-aadl-layout>
