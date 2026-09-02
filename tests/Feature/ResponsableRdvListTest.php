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

class ResponsableRdvListTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_responsable_can_open_rdv_list(): void
    {
        $responsable = $this->createResponsable();

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs')
            ->assertOk()
            ->assertSee('Liste des rendez-vous');
    }

    public function test_guest_cannot_access_rdv_list(): void
    {
        $this->get('/responsable/rdvs')
            ->assertRedirect('/responsable/login');
    }

    public function test_souscripteur_cannot_access_rdv_list(): void
    {
        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->get('/responsable/rdvs')
            ->assertRedirect('/responsable/login');
    }

    public function test_agent_cannot_access_rdv_list(): void
    {
        $this->actingAs($this->createAgent(), 'agent')
            ->get('/responsable/rdvs')
            ->assertRedirect('/responsable/login');
    }

    public function test_admin_cannot_access_rdv_list(): void
    {
        $this->actingAs($this->createAdmin(), 'admin')
            ->get('/responsable/rdvs')
            ->assertRedirect('/responsable/login');
    }

    public function test_responsable_sees_rdvs_only_from_their_own_dr(): void
    {
        $dr = Dr::create(['nom' => 'DR Alger']);
        $otherDr = Dr::create(['nom' => 'DR Oran']);
        $responsable = $this->createResponsable($dr);
        $souscripteur = $this->createSouscripteur();

        $this->createRdv($souscripteur, $dr, ['motif' => 'RDV Alger']);
        $this->createRdv($souscripteur, $otherDr, ['motif' => 'RDV Oran']);

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs')
            ->assertOk()
            ->assertSee('RDV Alger')
            ->assertDontSee('RDV Oran');
    }

    public function test_souscripteur_information_is_displayed(): void
    {
        $dr = Dr::create(['nom' => 'DR Alger']);
        $responsable = $this->createResponsable($dr);
        $souscripteur = $this->createSouscripteur();
        $this->createRdv($souscripteur, $dr);

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs')
            ->assertOk()
            ->assertSee('SUB001')
            ->assertSee('Test')
            ->assertSee('Souscripteur')
            ->assertSee('111111111111111111');
    }

    public function test_motif_date_and_french_status_label_are_displayed(): void
    {
        $dr = Dr::create(['nom' => 'DR Alger']);
        $responsable = $this->createResponsable($dr);
        $souscripteur = $this->createSouscripteur();
        $this->createRdv($souscripteur, $dr, [
            'motif' => 'Depot de dossier',
            'date' => '2026-09-15',
            'statut' => Rdv::STATUT_RDV_PRIS,
        ]);

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs')
            ->assertOk()
            ->assertSee('Depot de dossier')
            ->assertSee('2026-09-15')
            ->assertSee('RDV pris');
    }

    public function test_rdvs_are_ordered_by_newest_date_first(): void
    {
        $dr = Dr::create(['nom' => 'DR Alger']);
        $responsable = $this->createResponsable($dr);
        $souscripteur = $this->createSouscripteur();

        $this->createRdv($souscripteur, $dr, ['motif' => 'Ancien RDV', 'date' => '2026-09-01']);
        $this->createRdv($souscripteur, $dr, ['motif' => 'Nouveau RDV', 'date' => '2026-10-01']);

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs')
            ->assertOk()
            ->assertSeeInOrder(['Nouveau RDV', 'Ancien RDV']);
    }

    public function test_valid_date_filter_returns_only_matching_rdvs(): void
    {
        $dr = Dr::create(['nom' => 'DR Alger']);
        $responsable = $this->createResponsable($dr);
        $souscripteur = $this->createSouscripteur();

        $this->createRdv($souscripteur, $dr, ['motif' => 'RDV filtre', 'date' => '2026-09-15']);
        $this->createRdv($souscripteur, $dr, ['motif' => 'RDV hors filtre', 'date' => '2026-09-16']);

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs?date=2026-09-15')
            ->assertOk()
            ->assertSee('RDV filtre')
            ->assertDontSee('RDV hors filtre');
    }

    public function test_invalid_date_filter_is_rejected_safely(): void
    {
        $responsable = $this->createResponsable();

        $this->actingAs($responsable, 'responsable')
            ->from('/responsable/rdvs')
            ->get('/responsable/rdvs?date=not-a-date')
            ->assertRedirect('/responsable/rdvs')
            ->assertSessionHasErrors('date');
    }

    public function test_empty_state_message_appears_when_no_rdv_exists_for_responsable_dr(): void
    {
        $responsable = $this->createResponsable();

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs')
            ->assertOk()
            ->assertSee('Aucun rendez-vous trouvé pour votre direction régionale.');
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

    private function createSouscripteur(array $attributes = []): Souscripteur
    {
        return Souscripteur::create(array_merge([
            'code' => 'SUB001',
            'nom' => 'Test',
            'prenom' => 'Souscripteur',
            'nin' => '111111111111111111',
            'prop' => 'F3',
            'wil' => 'Alger',
            'password' => Hash::make('password'),
        ], $attributes));
    }

    private function createResponsable(?Dr $dr = null): Responsable
    {
        $dr ??= Dr::create(['nom' => 'DR Alger']);

        return Responsable::create([
            'email' => 'responsable@aadl.test',
            'password' => Hash::make('password'),
            'dr_id' => $dr->id,
        ]);
    }

    private function createAgent(): Agent
    {
        $dr = Dr::create(['nom' => 'DR Alger']);

        return Agent::create([
            'email' => 'agent@aadl.test',
            'password' => Hash::make('password'),
            'dr_id' => $dr->id,
        ]);
    }

    private function createAdmin(): Admin
    {
        return Admin::create([
            'email' => 'admin@aadl.test',
            'password' => Hash::make('password'),
        ]);
    }
}
