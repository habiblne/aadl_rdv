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

class AppointmentSheetTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 1;

    public function test_hashid_encodes_and_decodes_valid_rdv_id(): void
    {
        $rdv = $this->createRdv($this->createSouscripteur(), $this->createDr());
        $hashids = app(RdvHashids::class);

        $hashid = $hashids->encode($rdv);

        $this->assertNotSame((string) $rdv->id, $hashid);
        $this->assertSame($rdv->id, $hashids->decode($hashid));
    }

    public function test_invalid_hashid_returns_404(): void
    {
        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->get('/souscripteur/rdvs/not-a-valid-hashid/fiche')
            ->assertNotFound();
    }

    public function test_hashid_for_non_existent_rdv_returns_404(): void
    {
        $hashid = app(RdvHashids::class)->encode(999999);

        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->get("/souscripteur/rdvs/{$hashid}/fiche")
            ->assertNotFound();
    }

    public function test_authenticated_souscripteur_can_open_own_accepted_rdv_sheet(): void
    {
        $souscripteur = $this->createSouscripteur();
        $rdv = $this->createRdv($souscripteur, $this->createDr(), [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->get(route('souscripteur.rdvs.fiche', $rdv->hashid))
            ->assertOk()
            ->assertSee('Fiche de rendez-vous');
    }

    public function test_souscripteur_cannot_open_another_souscripteurs_sheet(): void
    {
        $owner = $this->createSouscripteur();
        $other = $this->createSouscripteur();
        $rdv = $this->createRdv($owner, $this->createDr(), [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($other, 'souscripteur')
            ->get(route('souscripteur.rdvs.fiche', $rdv->hashid))
            ->assertNotFound();
    }

    public function test_guest_cannot_open_sheet(): void
    {
        $rdv = $this->createRdv($this->createSouscripteur(), $this->createDr(), [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->get(route('souscripteur.rdvs.fiche', $rdv->hashid))
            ->assertRedirect('/souscripteur/login');
    }

    public function test_responsable_cannot_open_souscripteur_sheet(): void
    {
        $dr = $this->createDr();
        $rdv = $this->createRdv($this->createSouscripteur(), $dr, [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($this->createResponsable($dr), 'responsable')
            ->get(route('souscripteur.rdvs.fiche', $rdv->hashid))
            ->assertRedirect('/souscripteur/login');
    }

    public function test_agent_cannot_open_souscripteur_sheet(): void
    {
        $dr = $this->createDr();
        $rdv = $this->createRdv($this->createSouscripteur(), $dr, [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($this->createAgent($dr), 'agent')
            ->get(route('souscripteur.rdvs.fiche', $rdv->hashid))
            ->assertRedirect('/souscripteur/login');
    }

    public function test_admin_cannot_open_souscripteur_sheet(): void
    {
        $rdv = $this->createRdv($this->createSouscripteur(), $this->createDr(), [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($this->createAdmin(), 'admin')
            ->get(route('souscripteur.rdvs.fiche', $rdv->hashid))
            ->assertRedirect('/souscripteur/login');
    }

    public function test_status_zero_rdv_does_not_expose_sheet(): void
    {
        $souscripteur = $this->createSouscripteur();
        $rdv = $this->createRdv($souscripteur, $this->createDr(), [
            'statut' => Rdv::STATUT_RDV_PRIS,
        ]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->get(route('souscripteur.rdvs.fiche', $rdv->hashid))
            ->assertRedirect('/souscripteur/rdvs')
            ->assertSessionHasErrors('rdv');
    }

    public function test_status_one_rdv_exposes_sheet(): void
    {
        $this->assertSheetAvailableForStatus(Rdv::STATUT_RDV_ACCEPTE);
    }

    public function test_status_two_rdv_exposes_sheet(): void
    {
        $this->assertSheetAvailableForStatus(Rdv::STATUT_RDV_VALIDE);
    }

    public function test_status_three_rdv_exposes_sheet(): void
    {
        $this->assertSheetAvailableForStatus(Rdv::STATUT_RDV_COMPLETE);
    }

    public function test_sheet_displays_correct_existing_souscripteur_and_rdv_data(): void
    {
        $souscripteur = $this->createSouscripteur([
            'code' => 'SUB777',
            'nom' => 'Test',
            'prenom' => 'Fiche',
            'nin' => '777777777777777777',
        ]);
        $dr = $this->createDr('Direction Régionale Alger');
        $rdv = $this->createRdv($souscripteur, $dr, [
            'motif' => 'Dépôt de dossier',
            'date' => '2026-09-20',
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->get(route('souscripteur.rdvs.fiche', $rdv->hashid))
            ->assertOk()
            ->assertSee('SUB777')
            ->assertSee('Test')
            ->assertSee('Fiche')
            ->assertSee('777777777777777777')
            ->assertSee('Direction Régionale Alger')
            ->assertSee('Dépôt de dossier')
            ->assertSee('2026-09-20')
            ->assertSee('RDV accepté');
    }

    public function test_qr_code_is_rendered(): void
    {
        $souscripteur = $this->createSouscripteur();
        $rdv = $this->createRdv($souscripteur, $this->createDr(), [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->get(route('souscripteur.rdvs.fiche', $rdv->hashid))
            ->assertOk()
            ->assertSee('<svg', false);
    }

    public function test_qr_content_uses_hashid_verification_url(): void
    {
        $souscripteur = $this->createSouscripteur();
        $rdv = $this->createRdv($souscripteur, $this->createDr(), [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $verificationUrl = route('agent.rdvs.verification', $rdv->hashid);

        $this->actingAs($souscripteur, 'souscripteur')
            ->get(route('souscripteur.rdvs.fiche', $rdv->hashid))
            ->assertOk()
            ->assertSee($verificationUrl, false);
    }

    public function test_qr_content_does_not_contain_numeric_rdv_id_as_plain_route_parameter(): void
    {
        $souscripteur = $this->createSouscripteur();
        $rdv = $this->createRdv($souscripteur, $this->createDr(), [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->get(route('souscripteur.rdvs.fiche', $rdv->hashid))
            ->assertOk()
            ->assertDontSee("/agent/rdvs/{$rdv->id}/verification", false);
    }

    public function test_qr_content_does_not_contain_personal_information(): void
    {
        $souscripteur = $this->createSouscripteur([
            'code' => 'SUB888',
            'nom' => 'Personnel',
            'prenom' => 'Secret',
            'nin' => '888888888888888888',
        ]);
        $rdv = $this->createRdv($souscripteur, $this->createDr(), [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $response = $this->actingAs($souscripteur, 'souscripteur')
            ->get(route('souscripteur.rdvs.fiche', $rdv->hashid));

        preg_match('/data-qr-content="([^"]+)"/', $response->getContent(), $matches);

        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertStringNotContainsString('SUB888', $matches[1]);
        $this->assertStringNotContainsString('Personnel', $matches[1]);
        $this->assertStringNotContainsString('Secret', $matches[1]);
        $this->assertStringNotContainsString('888888888888888888', $matches[1]);
    }

    public function test_agent_verification_route_redirects_guests_to_agent_login(): void
    {
        $rdv = $this->createRdv($this->createSouscripteur(), $this->createDr(), [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->get(route('agent.rdvs.verification', $rdv->hashid))
            ->assertRedirect('/agent/login');
    }

    public function test_authenticated_agent_can_open_verification_placeholder_for_own_dr(): void
    {
        $dr = $this->createDr();
        $rdv = $this->createRdv($this->createSouscripteur(), $dr, [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($this->createAgent($dr), 'agent')
            ->get(route('agent.rdvs.verification', $rdv->hashid))
            ->assertOk()
            ->assertSee('Vérification du rendez-vous');
    }

    public function test_agent_cannot_open_verification_for_rdv_from_another_dr(): void
    {
        $agentDr = $this->createDr('Direction Régionale Alger');
        $otherDr = $this->createDr('Direction Générale AADL');
        $rdv = $this->createRdv($this->createSouscripteur(), $otherDr, [
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);

        $this->actingAs($this->createAgent($agentDr), 'agent')
            ->get(route('agent.rdvs.verification', $rdv->hashid))
            ->assertNotFound();
    }

    public function test_souscripteur_rdv_list_shows_sheet_link_only_for_statuses_one_two_and_three(): void
    {
        $souscripteur = $this->createSouscripteur();
        $dr = $this->createDr();

        $statusZero = $this->createRdv($souscripteur, $dr, ['statut' => Rdv::STATUT_RDV_PRIS]);
        $statusOne = $this->createRdv($souscripteur, $dr, ['statut' => Rdv::STATUT_RDV_ACCEPTE]);
        $statusTwo = $this->createRdv($souscripteur, $dr, ['statut' => Rdv::STATUT_RDV_VALIDE]);
        $statusThree = $this->createRdv($souscripteur, $dr, ['statut' => Rdv::STATUT_RDV_COMPLETE]);

        $response = $this->actingAs($souscripteur, 'souscripteur')
            ->get('/souscripteur/rdvs')
            ->assertOk()
            ->assertSee('En attente d\'acceptation', false)
            ->assertDontSee(route('souscripteur.rdvs.fiche', $statusZero->hashid), false)
            ->assertSee(route('souscripteur.rdvs.fiche', $statusOne->hashid), false)
            ->assertSee(route('souscripteur.rdvs.fiche', $statusTwo->hashid), false)
            ->assertSee(route('souscripteur.rdvs.fiche', $statusThree->hashid), false);

        $this->assertSame(3, substr_count($response->getContent(), 'Voir la fiche'));
    }

    private function assertSheetAvailableForStatus(int $status): void
    {
        $souscripteur = $this->createSouscripteur();
        $rdv = $this->createRdv($souscripteur, $this->createDr(), [
            'statut' => $status,
        ]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->get(route('souscripteur.rdvs.fiche', $rdv->hashid))
            ->assertOk()
            ->assertSee('Fiche de rendez-vous');
    }

    private function createRdv(Souscripteur $souscripteur, Dr $dr, array $attributes = []): Rdv
    {
        return Rdv::create(array_merge([
            'souscripteur_id' => $souscripteur->id,
            'dr_id' => $dr->id,
            'motif' => 'Depot de dossier',
            'date' => '2026-09-15',
            'statut' => Rdv::STATUT_RDV_PRIS,
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

    private function createResponsable(Dr $dr): Responsable
    {
        return Responsable::create([
            'email' => 'responsable'.$this->nextSuffix().'@aadl.test',
            'password' => Hash::make('password'),
            'dr_id' => $dr->id,
        ]);
    }

    private function createAgent(Dr $dr): Agent
    {
        return Agent::create([
            'email' => 'agent'.$this->nextSuffix().'@aadl.test',
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
