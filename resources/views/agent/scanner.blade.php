<x-aadl-layout
    actor-label="Agent"
    :user-identifier="Auth::guard('agent')->user()->email"
    dashboard-route="agent.dashboard"
    logout-route="agent.logout"
    :nav-links="[
        ['label' => 'Scanner un QR Code', 'url' => route('agent.scanner')],
    ]"
>
    <div data-agent-scanner data-verification-pattern="{{ $verificationPattern }}" class="max-w-3xl">
        <x-page-title title="Scanner un QR Code" subtitle="Autorisez l'accès à la caméra, puis scannez le QR Code présent sur la fiche de rendez-vous." />

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-800">
                Sur certains navigateurs, la caméra fonctionne uniquement en HTTPS. En développement local, localhost et 127.0.0.1 sont généralement acceptés.
            </p>

            <div id="agent-qr-reader" class="min-h-64 overflow-hidden rounded-lg border border-slate-300 bg-slate-50"></div>

            <p data-scanner-message class="mt-4 rounded-md bg-slate-50 p-3 text-sm text-slate-700">
                Scanner prêt.
            </p>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                <x-primary-button type="button" data-scanner-start>
                    Démarrer le scanner
                </x-primary-button>

                <x-secondary-button type="button" data-scanner-stop>
                    Arrêter le scanner
                </x-secondary-button>
            </div>
        </section>

        <div class="mt-6">
            <a href="{{ route('agent.dashboard') }}" class="text-sm font-medium text-emerald-700 underline">
                Retour au tableau de bord
            </a>
        </div>
    </div>
</x-aadl-layout>
