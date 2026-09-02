<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Agent;
use App\Models\Dr;
use App\Models\Rdv;
use App\Models\Responsable;
use App\Models\Souscripteur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AgentScannerTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 1;

    public function test_authenticated_agent_can_open_scanner(): void
    {
        $agent = $this->createAgent($this->createDr());

        $this->actingAs($agent, 'agent')
            ->get('/agent/scanner')
            ->assertOk()
            ->assertSee('Scanner un QR Code')
            ->assertSee('agent-qr-reader');
    }

    public function test_guest_is_redirected_to_agent_login(): void
    {
        $this->get('/agent/scanner')
            ->assertRedirect('/agent/login');
    }

    public function test_souscripteur_cannot_access_scanner_page(): void
    {
        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->get('/agent/scanner')
            ->assertRedirect('/agent/login');
    }

    public function test_responsable_cannot_access_scanner_page(): void
    {
        $this->actingAs($this->createResponsable($this->createDr()), 'responsable')
            ->get('/agent/scanner')
            ->assertRedirect('/agent/login');
    }

    public function test_admin_cannot_access_scanner_page(): void
    {
        $this->actingAs($this->createAdmin(), 'admin')
            ->get('/agent/scanner')
            ->assertRedirect('/agent/login');
    }

    public function test_agent_dashboard_contains_scanner_link(): void
    {
        $agent = $this->createAgent($this->createDr());

        $this->actingAs($agent, 'agent')
            ->get('/agent/dashboard')
            ->assertOk()
            ->assertSee('Scanner un QR Code')
            ->assertSee('/agent/scanner');
    }

    public function test_scanner_page_contains_expected_verification_route_pattern(): void
    {
        $agent = $this->createAgent($this->createDr());

        $this->actingAs($agent, 'agent')
            ->get('/agent/scanner')
            ->assertOk()
            ->assertSee('/agent/rdvs/{hashid}/verification', false);
    }

    public function test_scanner_page_does_not_expose_personal_information(): void
    {
        $this->createSouscripteur([
            'code' => 'SUBPRIVATE',
            'nom' => 'Secret',
            'prenom' => 'Personne',
            'nin' => '999999999999999999',
        ]);

        $this->actingAs($this->createAgent($this->createDr()), 'agent')
            ->get('/agent/scanner')
            ->assertOk()
            ->assertDontSee('SUBPRIVATE')
            ->assertDontSee('Secret')
            ->assertDontSee('Personne')
            ->assertDontSee('999999999999999999');
    }

    public function test_existing_agent_verification_security_still_works(): void
    {
        $ownDr = $this->createDr('Direction Régionale Alger');
        $otherDr = $this->createDr('Direction Générale AADL');
        $agent = $this->createAgent($ownDr);
        $ownRdv = $this->createRdv($this->createSouscripteur(), $ownDr);
        $otherRdv = $this->createRdv($this->createSouscripteur(), $otherDr);

        $this->actingAs($agent, 'agent')
            ->get(route('agent.rdvs.verification', $ownRdv->hashid))
            ->assertOk();

        $this->actingAs($agent, 'agent')
            ->get(route('agent.rdvs.verification', $otherRdv->hashid))
            ->assertNotFound();
    }

    public function test_existing_agent_validation_from_one_to_two_still_works(): void
    {
        $dr = $this->createDr();
        $agent = $this->createAgent($dr);
        $rdv = $this->createRdv($this->createSouscripteur(), $dr, [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($agent, 'agent')
            ->patch(route('agent.rdvs.valider', $rdv->hashid))
            ->assertRedirect(route('agent.rdvs.verification', $rdv->hashid));

        $this->assertSame(Rdv::STATUT_RDV_VALIDE, $rdv->fresh()->statut);
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
