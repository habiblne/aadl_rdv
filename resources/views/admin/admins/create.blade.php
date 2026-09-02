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
    <x-page-title title="Ajouter un administrateur" subtitle="Créer un nouveau compte administrateur." />

    <form method="POST" action="{{ route('admin.admins.store') }}" class="max-w-3xl space-y-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" class="mt-1 block w-full" type="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Mot de passe" />
            <x-text-input id="password" name="password" class="mt-1 block w-full" type="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmation du mot de passe" />
            <x-text-input id="password_confirmation" name="password_confirmation" class="mt-1 block w-full" type="password" required autocomplete="new-password" />
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.admins.index') }}" class="text-sm font-medium text-emerald-700 underline">Retour à la liste</a>
            <x-primary-button>Ajouter l’administrateur</x-primary-button>
        </div>
    </form>
</x-aadl-layout>
