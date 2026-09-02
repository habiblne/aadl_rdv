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
    <x-page-title title="Prendre un rendez-vous" subtitle="Choisissez la Direction Générale ou votre direction régionale, indiquez le motif, puis sélectionnez une date disponible." />

    <form method="POST" action="{{ route('souscripteur.rdvs.store') }}" class="max-w-3xl space-y-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" id="rdv-form">
        @csrf

        @if ($assignmentError)
            <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                {{ $assignmentError }}
            </div>
        @endif

        <div>
            <x-input-label for="dr_id" value="Direction" />
            <select id="dr_id" name="dr_id" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 disabled:bg-slate-100 disabled:text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:disabled:bg-slate-800 dark:disabled:text-slate-500" @if ($directions->isEmpty()) disabled @endif>
                @foreach ($directions as $direction)
                    <option value="{{ $direction->id }}" @selected((string) old('dr_id', $directions->first()?->id) === (string) $direction->id)>
                        {{ $direction->nom }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('rdv')" class="mt-2" />
            <x-input-error :messages="$errors->get('dr_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="motif" value="Motif" />
            <textarea id="motif" name="motif" required rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">{{ old('motif') }}</textarea>
            <x-input-error :messages="$errors->get('motif')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="date" value="Date du rendez-vous" />
            <input id="date" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 disabled:bg-slate-100 disabled:text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:disabled:bg-slate-800 dark:disabled:text-slate-500" type="date" name="date" value="{{ old('date') }}" min="{{ $minDate }}" required @if ($directions->isEmpty()) disabled @endif />
            <p class="mt-2 rounded-md bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">Les rendez-vous doivent être réservés au moins 3 jours à l'avance.</p>
            <p id="availability-status" data-availability-url="{{ route('souscripteur.rdvs.indisponibilites') }}" class="mt-2 rounded-md bg-slate-50 p-3 text-sm text-slate-700 dark:bg-slate-950 dark:text-slate-300">
                Vérification des disponibilités...
            </p>
            <div id="full-dates-list" class="mt-2 hidden rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100"></div>
            <x-input-error :messages="$errors->get('date')" class="mt-2" />
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('souscripteur.dashboard') }}" class="text-sm font-medium text-emerald-700 underline dark:text-emerald-400">
                Retour au tableau de bord
            </a>

            <x-primary-button id="submit-rdv" :disabled="$directions->isEmpty()">Confirmer le rendez-vous</x-primary-button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dateInput = document.getElementById('date');
            const directionSelect = document.getElementById('dr_id');
            const status = document.getElementById('availability-status');
            const fullDatesList = document.getElementById('full-dates-list');
            const submitButton = document.getElementById('submit-rdv');
            let fullDates = [];

            const setSubmitState = () => {
                submitButton.disabled = dateInput.disabled || directionSelect.disabled || !directionSelect.value || !dateInput.value || fullDates.includes(dateInput.value);
            };

            const renderFullDates = () => {
                if (fullDates.length === 0) {
                    fullDatesList.classList.add('hidden');
                    fullDatesList.textContent = '';
                    return;
                }

                fullDatesList.classList.remove('hidden');
                fullDatesList.innerHTML = `<p class="font-semibold">Dates complètes:</p><ul class="mt-1 list-disc ps-5">${fullDates.map((date) => `<li>${date} - Complet - 30 rendez-vous</li>`).join('')}</ul>`;
            };

            const validateSelectedDate = () => {
                if (!dateInput.value) {
                    setSubmitState();
                    return;
                }

                if (fullDates.includes(dateInput.value)) {
                    dateInput.value = '';
                    status.textContent = 'Cette date est complète pour la direction sélectionnée. Complet - 30 rendez-vous.';
                    status.className = 'mt-2 rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-100';
                } else {
                    status.textContent = 'Disponible';
                    status.className = 'mt-2 rounded-md bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-100';
                }

                setSubmitState();
            };

            const loadAvailability = async () => {
                fullDates = [];
                status.className = 'mt-2 rounded-md bg-slate-50 p-3 text-sm text-slate-700 dark:bg-slate-950 dark:text-slate-300';

                if (dateInput.disabled || directionSelect.disabled || !directionSelect.value) {
                    status.textContent = 'Aucune direction n’est disponible pour votre compte.';
                    renderFullDates();
                    setSubmitState();
                    return;
                }

                status.textContent = 'Vérification des disponibilités...';
                setSubmitState();

                try {
                    const url = `${status.dataset.availabilityUrl}?dr_id=${encodeURIComponent(directionSelect.value)}`;
                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('availability');
                    }

                    const data = await response.json();
                    fullDates = data.dates_completes || [];
                    status.textContent = 'Disponible';
                    status.className = 'mt-2 rounded-md bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-100';
                    renderFullDates();
                    validateSelectedDate();
                } catch (error) {
                    dateInput.disabled = true;
                    status.textContent = 'Impossible de charger les disponibilités. Veuillez réessayer.';
                    status.className = 'mt-2 rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-100';
                    renderFullDates();
                    setSubmitState();
                }
            };

            dateInput.addEventListener('change', validateSelectedDate);
            directionSelect.addEventListener('change', loadAvailability);
            document.getElementById('rdv-form').addEventListener('submit', (event) => {
                if (fullDates.includes(dateInput.value)) {
                    event.preventDefault();
                    validateSelectedDate();
                }
            });

            loadAvailability();
        });
    </script>
</x-aadl-layout>
