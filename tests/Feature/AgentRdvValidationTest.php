<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Agent;
use App\Models\Dr;
use App\Models\Rdv;
use App\Models\Responsable;
use App\Models\Souscripteur;
use App\Support\RdvHashids;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AgentRdvValidationTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 1;

    public function test_authenticated_agent_can_validate_status_one_rdv_from_their_own_dr(): void
    {
        [$agent, $rdv] = $this->createAgentWithRdv(Rdv::STATUT_RDV_ACCEPTE);

        $this->actingAs($agent, 'agent')
            ->patch(route('agent.rdvs.valider', $rdv->hashid))
            ->assertRedirect(route('agent.rdvs.verification', $rdv->hashid))
            ->assertSessionHas('status', 'Le rendez-vous a été validé.');

        $this->assertSame(Rdv::STATUT_RDV_VALIDE, $rdv->fresh()->statut);
    }

    public function test_status_changes_from_one_to_two(): void
    {
        [$agent, $rdv] = $this->createAgentWithRdv(Rdv::STATUT_RDV_ACCEPTE);

        $this->actingAs($agent, 'agent')
            ->patch(route('agent.rdvs.valider', $rdv->hashid));

        $this->assertDatabaseHas('rdvs', [
            'id' => $rdv->id,
            'statut' => Rdv::STATUT_RDV_VALIDE,
        ]);
    }

    public function test_agent_cannot_validate_rdv_from_another_dr(): void
    {
        $agentDr = $this->createDr('Direction Régionale Alger');
        $otherDr = $this->createDr('Direction Générale AADL');
        $agent = $this->createAgent($agentDr);
        $rdv = $this->createRdv($this->createSouscripteur(), $otherDr, [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($agent, 'agent')
            ->patch(route('agent.rdvs.valider', $rdv->hashid))
            ->assertNotFound();

        $this->assertSame(Rdv::STATUT_RDV_ACCEPTE, $rdv->fresh()->statut);
    }

    public function test_souscripteur_cannot_use_validation_route(): void
    {
        [, $rdv] = $this->createAgentWithRdv(Rdv::STATUT_RDV_ACCEPTE);

        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->patch(route('agent.rdvs.valider', $rdv->hashid))
            ->assertRedirect('/agent/login');
    }

    public function test_responsable_cannot_use_validation_route(): void
    {
        [$agent, $rdv] = $this->createAgentWithRdv(Rdv::STATUT_RDV_ACCEPTE);

        $this->actingAs($this->createResponsable($agent->dr), 'responsable')
            ->patch(route('agent.rdvs.valider', $rdv->hashid))
            ->assertRedirect('/agent/login');
    }

    public function test_admin_cannot_use_validation_route(): void
    {
        [, $rdv] = $this->createAgentWithRdv(Rdv::STATUT_RDV_ACCEPTE);

        $this->actingAs($this->createAdmin(), 'admin')
            ->patch(route('agent.rdvs.valider', $rdv->hashid))
            ->assertRedirect('/agent/login');
    }

    public function test_guest_cannot_use_validation_route(): void
    {
        [, $rdv] = $this->createAgentWithRdv(Rdv::STATUT_RDV_ACCEPTE);

        $this->patch(route('agent.rdvs.valider', $rdv->hashid))
            ->assertRedirect('/agent/login');
    }

    public function test_status_zero_cannot_be_validated(): void
    {
        $this->assertStatusCannotBeValidated(Rdv::STATUT_RDV_PRIS);
    }

    public function test_status_two_cannot_be_validated_again(): void
    {
        $this->assertStatusCannotBeValidated(Rdv::STATUT_RDV_VALIDE);
    }

    public function test_status_three_cannot_be_validated(): void
    {
        $this->assertStatusCannotBeValidated(Rdv::STATUT_RDV_COMPLETE);
    }

    public function test_validation_button_appears_only_for_status_one(): void
    {
        [$agent, $rdv] = $this->createAgentWithRdv(Rdv::STATUT_RDV_ACCEPTE);

        $this->actingAs($agent, 'agent')
            ->get(route('agent.rdvs.verification', $rdv->hashid))
            ->assertOk()
            ->assertSee('Valider la présence')
            ->assertSee(route('agent.rdvs.valider', $rdv->hashid), false);
    }

    public function test_validation_button_is_hidden_for_statuses_zero_two_and_three(): void
    {
        foreach ([Rdv::STATUT_RDV_PRIS, Rdv::STATUT_RDV_VALIDE, Rdv::STATUT_RDV_COMPLETE] as $status) {
            [$agent, $rdv] = $this->createAgentWithRdv($status);

            $this->actingAs($agent, 'agent')
                ->get(route('agent.rdvs.verification', $rdv->hashid))
                ->assertOk()
                ->assertDontSee('Valider la présence')
                ->assertDontSee(route('agent.rdvs.valider', $rdv->hashid), false);
        }
    }

    public function test_invalid_hashid_returns_404(): void
    {
        $this->actingAs($this->createAgent($this->createDr()), 'agent')
            ->patch('/agent/rdvs/not-a-valid-hashid/valider')
            ->assertNotFound();
    }

    public function test_non_existent_hashid_returns_404(): void
    {
        $hashid = app(RdvHashids::class)->encode(999999);

        $this->actingAs($this->createAgent($this->createDr()), 'agent')
            ->patch(route('agent.rdvs.valider', $hashid))
            ->assertNotFound();
    }

    public function test_conditional_update_protects_against_concurrent_status_changes(): void
    {
        [$agent, $rdv] = $this->createAgentWithRdv(Rdv::STATUT_RDV_ACCEPTE);
        $rdv->update(['statut' => Rdv::STATUT_RDV_COMPLETE]);

        $this->actingAs($agent, 'agent')
            ->patch(route('agent.rdvs.valider', $rdv->hashid))
            ->assertRedirect(route('agent.rdvs.verification', $rdv->hashid))
            ->assertSessionHasErrors('rdv');

        $this->assertSame(Rdv::STATUT_RDV_COMPLETE, $rdv->fresh()->statut);
    }

    public function test_existing_agent_verification_security_remains_valid(): void
    {
        $ownDr = $this->createDr('Direction Régionale Alger');
        $otherDr = $this->createDr('Direction Générale AADL');
        $agent = $this->createAgent($ownDr);
        $ownRdv = $this->createRdv($this->createSouscripteur(), $ownDr, [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);
        $otherRdv = $this->createRdv($this->createSouscripteur(), $otherDr, [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($agent, 'agent')
            ->get(route('agent.rdvs.verification', $ownRdv->hashid))
            ->assertOk();

        $this->actingAs($agent, 'agent')
            ->get(route('agent.rdvs.verification', $otherRdv->hashid))
            ->assertNotFound();
    }

    public function test_agent_dashboard_contains_verification_link(): void
    {
        $agent = $this->createAgent($this->createDr());

        $this->actingAs($agent, 'agent')
            ->get('/agent/dashboard')
            ->assertOk()
            ->assertSee('Scanner un QR Code')
            ->assertSee('/agent/scanner');
    }

    private function assertStatusCannotBeValidated(int $status): void
    {
        [$agent, $rdv] = $this->createAgentWithRdv($status);

        $this->actingAs($agent, 'agent')
            ->patch(route('agent.rdvs.valider', $rdv->hashid))
            ->assertRedirect(route('agent.rdvs.verification', $rdv->hashid))
            ->assertSessionHasErrors('rdv');

        $this->assertSame($status, $rdv->fresh()->statut);
    }

    private function createAgentWithRdv(int $status): array
    {
        $dr = $this->createDr();
        $agent = $this->createAgent($dr);
        $rdv = $this->createRdv($this->createSouscripteur(), $dr, [
            'statut' => $status,
        ]);

        return [$agent, $rdv];
    }

    private function createRdv(Souscripteur $souscripteur, Dr $dr, array $attributes = []): Rdv
    {
        return Rdv::create(array_merge([
            'souscripteur_id' => $souscripteur->id,
            'dr_id' => $dr->id,
            'motif' => 'Depot de dossier',
            'date' => '2026-09-15',
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ], $attributes));
    }

    private function createDr(string $nom = 'Direction Régionale Alger'): Dr
    {
        return Dr::create(['nom' => $nom.' '.$this->nextSuffix()]);
    }

    private function createSouscripteur(array $attributes = []): Souscripteur
    {
        $suffix = $this->nextSuffix();

        return Souscripteur::create(array_merge([
            'code' => "SUB{$suffix}",
            'nom' => 'Test',
            'prenom' => 'Souscripteur',
            'nin' => str_pad($suffix, 18, '1', STR_PAD_LEFT),
            'prop' => 'F3',
            'wil' => 'Alger',
            'password' => Hash::make('password'),
        ], $attributes));
    }

    private function createAgent(Dr $dr): Agent
    {
        return Agent::create([
            'email' => 'agent'.$this->nextSuffix().'@aadl.test',
            'password' => Hash::make('password'),
            'dr_id' => $dr->id,
        ]);
    }

    private function createResponsable(Dr $dr): Responsable
    {
        return Responsable::create([
            'email' => 'responsable'.$this->nextSuffix().'@aadl.test',
            'password' => Hash::make('password'),
            'dr_id' => $dr->id,
        ]);
    }

    private function createAdmin(): Admin
    {
        return Admin::create([
            'email' => 'admin'.$this->nextSuffix().'@aadl.test',
            'password' => Hash::make('password'),
        ]);
    }

    private function nextSuffix(): string
    {
        return str_pad((string) $this->sequence++, 3, '0', STR_PAD_LEFT);
    }
}
