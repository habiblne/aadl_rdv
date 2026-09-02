<?php

namespace App\Http\Controllers;

use App\Models\Rdv;
use App\Support\RdvHashids;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AgentRdvVerificationController extends Controller
{
    public function show(string $hashid, RdvHashids $hashids): View
    {
        $rdv = $hashids->findOrFail($hashid);
        $agent = Auth::guard('agent')->user();

        abort_unless($rdv->dr_id === $agent->dr_id, 404);

        return view('agent.rdvs.verification', [
            'rdv' => $rdv,
        ]);
    }

    public function valider(string $hashid, RdvHashids $hashids): RedirectResponse
    {
        $rdv = $hashids->findOrFail($hashid);
        $agent = Auth::guard('agent')->user();

        abort_unless($rdv->dr_id === $agent->dr_id, 404);

        $updated = Rdv::query()
            ->whereKey($rdv->id)
            ->where('dr_id', $agent->dr_id)
            ->where('statut', Rdv::STATUT_RDV_ACCEPTE)
            ->update([
                'statut' => Rdv::STATUT_RDV_VALIDE,
                'validated_by_agent_id' => $agent->id,
                'validated_at' => Carbon::now(),
            ]);

        if (! $updated) {
            return redirect()
                ->route('agent.rdvs.verification', $hashid)
                ->withErrors(['rdv' => 'Ce rendez-vous ne peut pas être validé.']);
        }

        return redirect()
            ->route('agent.rdvs.verification', $hashid)
            ->with('status', 'Le rendez-vous a été validé.');
    }
}
