<?php

namespace App\Http\Controllers;

use App\Models\Rdv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ResponsableRdvController extends Controller
{
    private const LIST_PER_PAGE = 15;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $rdvs = Auth::guard('responsable')
            ->user()
            ->dr
            ->rdvs()
            ->with('souscripteur')
            ->when($validated['date'] ?? null, function ($query, string $date) {
                $query->whereDate('date', $date);
            })
            ->orderByDesc('date')
            ->latest()
            ->paginate(self::LIST_PER_PAGE)
            ->withQueryString();

        return view('responsable.rdvs.index', [
            'rdvs' => $rdvs,
            'selectedDate' => $validated['date'] ?? null,
        ]);
    }

    public function accepter(Rdv $rdv): RedirectResponse
    {
        $responsable = Auth::guard('responsable')->user();

        abort_unless($rdv->dr_id === $responsable->dr_id, 404);

        $updated = Rdv::query()
            ->whereKey($rdv->id)
            ->where('dr_id', $responsable->dr_id)
            ->where('statut', Rdv::STATUT_RDV_PRIS)
            ->update([
                'statut' => Rdv::STATUT_RDV_ACCEPTE,
                'accepted_by_responsable_id' => $responsable->id,
                'accepted_at' => Carbon::now(),
            ]);

        if (! $updated) {
            return redirect()
                ->route('responsable.rdvs.index')
                ->withErrors(['rdv' => 'Ce rendez-vous ne peut pas être accepté.']);
        }

        return redirect()
            ->route('responsable.rdvs.index')
            ->with('status', 'Le rendez-vous a été accepté.');
    }

    public function completer(Rdv $rdv): RedirectResponse
    {
        $responsable = Auth::guard('responsable')->user();

        abort_unless($rdv->dr_id === $responsable->dr_id, 404);

        $updated = Rdv::query()
            ->whereKey($rdv->id)
            ->where('dr_id', $responsable->dr_id)
            ->where('statut', Rdv::STATUT_RDV_VALIDE)
            ->update([
                'statut' => Rdv::STATUT_RDV_COMPLETE,
                'completed_by_responsable_id' => $responsable->id,
                'completed_at' => Carbon::now(),
            ]);

        if (! $updated) {
            return redirect()
                ->route('responsable.rdvs.index')
                ->withErrors(['rdv' => 'Ce rendez-vous ne peut pas être complété.']);
        }

        return redirect()
            ->route('responsable.rdvs.index')
            ->with('status', 'Le rendez-vous a été complété.');
    }
}
