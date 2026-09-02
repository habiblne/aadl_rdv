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
    <x-page-title title="Modifier le responsable" subtitle="Mettre à jour l'email et la direction du responsable." />

    <form method="POST" action="{{ route('admin.responsables.update', $responsable) }}" class="max-w-3xl space-y-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        @csrf
        @method('PATCH')

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" class="mt-1 block w-full" type="email" :value="old('email', $responsable->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="dr_id" value="Direction" />
            <select id="dr_id" name="dr_id" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                <option value="">Sélectionner une direction</option>
                @foreach ($drs as $dr)
                    <option value="{{ $dr->id }}" @selected((string) old('dr_id', $responsable->dr_id) === (string) $dr->id)>{{ $dr->nom }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('dr_id')" class="mt-2" />
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.responsables.index') }}" class="text-sm font-medium text-emerald-700 underline">Retour à la liste</a>
            <x-primary-button>Enregistrer</x-primary-button>
        </div>
    </form>
</x-aadl-layout>
