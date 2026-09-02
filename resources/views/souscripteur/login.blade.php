<x-guest-layout>
    <x-page-title title="Connexion Souscripteur" subtitle="Accédez à votre espace personnel avec votre code souscripteur." />

    <form method="POST" action="{{ route('souscripteur.login.store') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="code" value="Code" />
            <x-text-input id="code" class="mt-1 block w-full" type="text" name="code" :value="old('code')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Mot de passe" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">Se connecter</x-primary-button>
    </form>
</x-guest-layout>
