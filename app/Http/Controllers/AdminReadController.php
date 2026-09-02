<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Agent;
use App\Models\Dr;
use App\Models\Rdv;
use App\Models\Responsable;
use App\Models\Souscripteur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminReadController extends Controller
{
    private const LIST_PER_PAGE = 15;

    public function souscripteurs(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = $validated['q'] ?? null;

        $souscripteurs = Souscripteur::query()
            ->when($query, function ($builder, string $query) {
                $builder->where(function ($builder) use ($query) {
                    $builder
                        ->where('code', 'like', "%{$query}%")
                        ->orWhere('nom', 'like', "%{$query}%")
                        ->orWhere('prenom', 'like', "%{$query}%")
                        ->orWhere('nin', 'like', "%{$query}%");
                });
            })
            ->latest()
            ->paginate(self::LIST_PER_PAGE)
            ->withQueryString();

        return view('admin.souscripteurs.index', [
            'souscripteurs' => $souscripteurs,
            'query' => $query,
        ]);
    }

    public function responsables(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = $validated['q'] ?? null;

        $responsables = Responsable::query()
            ->with('dr')
            ->when($query, fn ($builder, string $query) => $builder->where('email', 'like', "%{$query}%"))
            ->latest()
            ->paginate(self::LIST_PER_PAGE)
            ->withQueryString();

        return view('admin.responsables.index', [
            'responsables' => $responsables,
            'query' => $query,
        ]);
    }

    public function createResponsable(): View
    {
        return view('admin.responsables.create', [
            'drs' => Dr::orderBy('nom')->get(),
        ]);
    }

    public function editResponsable(Responsable $responsable): View
    {
        return view('admin.responsables.edit', [
            'responsable' => $responsable,
            'drs' => Dr::orderBy('nom')->get(),
        ]);
    }

    public function storeResponsable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:responsables,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'dr_id' => ['required', 'integer', 'exists:drs,id'],
        ], $this->creationMessages());

        Responsable::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'dr_id' => $validated['dr_id'],
        ]);

        return redirect()
            ->route('admin.responsables.index')
            ->with('status', 'Le responsable a été ajouté.');
    }

    public function updateResponsable(Request $request, Responsable $responsable): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', Rule::unique('responsables', 'email')->ignore($responsable)],
            'dr_id' => ['required', 'integer', 'exists:drs,id'],
        ], $this->editMessages());

        $responsable->update([
            'email' => $validated['email'],
            'dr_id' => $validated['dr_id'],
        ]);

        return redirect()
            ->route('admin.responsables.index')
            ->with('status', 'Le responsable a été modifié.');
    }

    public function destroyResponsable(Responsable $responsable): RedirectResponse
    {
        try {
            $responsable->delete();
        } catch (\Throwable) {
            return redirect()
                ->route('admin.responsables.index')
                ->withErrors(['responsable' => 'Le responsable ne peut pas être supprimé.']);
        }

        return redirect()
            ->route('admin.responsables.index')
            ->with('status', 'Le responsable a été supprimé.');
    }

    public function editResponsablePassword(Responsable $responsable): View
    {
        return view('admin.responsables.password', [
            'responsable' => $responsable,
        ]);
    }

    public function updateResponsablePassword(Request $request, Responsable $responsable): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], $this->passwordMessages());

        $responsable->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.responsables.index')
            ->with('status', 'Le mot de passe du responsable a été réinitialisé.');
    }

    public function agents(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = $validated['q'] ?? null;

        $agents = Agent::query()
            ->with('dr')
            ->when($query, fn ($builder, string $query) => $builder->where('email', 'like', "%{$query}%"))
            ->latest()
            ->paginate(self::LIST_PER_PAGE)
            ->withQueryString();

        return view('admin.agents.index', [
            'agents' => $agents,
            'query' => $query,
        ]);
    }

    public function createAgent(): View
    {
        return view('admin.agents.create', [
            'drs' => Dr::orderBy('nom')->get(),
        ]);
    }

    public function editAgent(Agent $agent): View
    {
        return view('admin.agents.edit', [
            'agent' => $agent,
            'drs' => Dr::orderBy('nom')->get(),
        ]);
    }

    public function storeAgent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:agents,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'dr_id' => ['required', 'integer', 'exists:drs,id'],
        ], $this->creationMessages());

        Agent::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'dr_id' => $validated['dr_id'],
        ]);

        return redirect()
            ->route('admin.agents.index')
            ->with('status', 'L’agent a été ajouté.');
    }

    public function updateAgent(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', Rule::unique('agents', 'email')->ignore($agent)],
            'dr_id' => ['required', 'integer', 'exists:drs,id'],
        ], $this->editMessages());

        $agent->update([
            'email' => $validated['email'],
            'dr_id' => $validated['dr_id'],
        ]);

        return redirect()
            ->route('admin.agents.index')
            ->with('status', 'L’agent a été modifié.');
    }

    public function destroyAgent(Agent $agent): RedirectResponse
    {
        try {
            $agent->delete();
        } catch (\Throwable) {
            return redirect()
                ->route('admin.agents.index')
                ->withErrors(['agent' => 'L’agent ne peut pas être supprimé.']);
        }

        return redirect()
            ->route('admin.agents.index')
            ->with('status', 'L’agent a été supprimé.');
    }

    public function editAgentPassword(Agent $agent): View
    {
        return view('admin.agents.password', [
            'agent' => $agent,
        ]);
    }

    public function updateAgentPassword(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], $this->passwordMessages());

        $agent->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.agents.index')
            ->with('status', 'Le mot de passe de l’agent a été réinitialisé.');
    }

    public function admins(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = $validated['q'] ?? null;

        $admins = Admin::query()
            ->when($query, fn ($builder, string $query) => $builder->where('email', 'like', "%{$query}%"))
            ->latest()
            ->paginate(self::LIST_PER_PAGE)
            ->withQueryString();

        return view('admin.admins.index', [
            'admins' => $admins,
            'totalAdmins' => Admin::count(),
            'query' => $query,
        ]);
    }

    public function createAdmin(): View
    {
        return view('admin.admins.create');
    }

    public function editAdmin(Admin $admin): View
    {
        return view('admin.admins.edit', [
            'admin' => $admin,
        ]);
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:admins,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], $this->creationMessages());

        Admin::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.admins.index')
            ->with('status', 'L’administrateur a été ajouté.');
    }

    public function updateAdmin(Request $request, Admin $admin): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', Rule::unique('admins', 'email')->ignore($admin)],
        ], $this->editMessages());

        $admin->update([
            'email' => $validated['email'],
        ]);

        return redirect()
            ->route('admin.admins.index')
            ->with('status', 'L’administrateur a été modifié.');
    }

    public function destroyAdmin(Admin $admin): RedirectResponse
    {
        $authenticatedAdmin = auth('admin')->user();

        return DB::transaction(function () use ($admin, $authenticatedAdmin) {
            if (Admin::query()->lockForUpdate()->count() <= 1) {
                return redirect()
                    ->route('admin.admins.index')
                    ->withErrors(['admin' => 'Le dernier administrateur ne peut pas être supprimé.']);
            }

            if ($authenticatedAdmin->id === $admin->id) {
                return redirect()
                    ->route('admin.admins.index')
                    ->withErrors(['admin' => 'Vous ne pouvez pas supprimer votre propre compte.']);
            }

            $admin->delete();

            return redirect()
                ->route('admin.admins.index')
                ->with('status', 'L’administrateur a été supprimé.');
        });
    }

    public function editAdminPassword(Admin $admin): View
    {
        return view('admin.admins.password', [
            'admin' => $admin,
        ]);
    }

    public function updateAdminPassword(Request $request, Admin $admin): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], $this->passwordMessages());

        $admin->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.admins.index')
            ->with('status', 'Le mot de passe de l’administrateur a été réinitialisé.');
    }

    public function rdvs(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'dr_id' => ['nullable', 'integer', 'exists:drs,id'],
            'statut' => ['nullable', 'integer', Rule::in([
                Rdv::STATUT_RDV_PRIS,
                Rdv::STATUT_RDV_ACCEPTE,
                Rdv::STATUT_RDV_VALIDE,
                Rdv::STATUT_RDV_COMPLETE,
            ])],
        ]);

        $rdvs = Rdv::query()
            ->with(['dr', 'souscripteur', 'acceptedByResponsable', 'validatedByAgent', 'completedByResponsable'])
            ->when($validated['date'] ?? null, fn ($builder, string $date) => $builder->whereDate('date', $date))
            ->when($validated['dr_id'] ?? null, fn ($builder, int $drId) => $builder->where('dr_id', $drId))
            ->when(
                array_key_exists('statut', $validated) && $validated['statut'] !== null,
                fn ($builder) => $builder->where('statut', $validated['statut'])
            )
            ->orderByDesc('date')
            ->latest()
            ->paginate(self::LIST_PER_PAGE)
            ->withQueryString();

        return view('admin.rdvs.index', [
            'rdvs' => $rdvs,
            'drs' => Dr::orderBy('nom')->get(),
            'filters' => [
                'date' => $validated['date'] ?? null,
                'dr_id' => $validated['dr_id'] ?? null,
                'statut' => $validated['statut'] ?? null,
            ],
        ]);
    }

    private function creationMessages(): array
    {
        return [
            'email.required' => 'L’email est obligatoire.',
            'email.email' => 'L’email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'dr_id.required' => 'La direction est obligatoire.',
            'dr_id.exists' => 'La direction sélectionnée est invalide.',
        ];
    }

    private function editMessages(): array
    {
        return [
            'email.required' => 'L’email est obligatoire.',
            'email.email' => 'L’email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'dr_id.required' => 'La direction est obligatoire.',
            'dr_id.exists' => 'La direction sélectionnée est invalide.',
        ];
    }

    private function passwordMessages(): array
    {
        return [
            'password.required' => 'Le nouveau mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du nouveau mot de passe ne correspond pas.',
            'password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
        ];
    }
}
