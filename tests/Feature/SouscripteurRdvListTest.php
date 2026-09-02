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

class SouscripteurRdvListTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_souscripteur_can_open_rdv_list(): void
    {
        $souscripteur = $this->createSouscripteur();

        $this->actingAs($souscripteur, 'souscripteur')
            ->get('/souscripteur/rdvs')
            ->assertOk()
            ->assertSee('Mes rendez-vous');
    }

    public function test_guest_cannot_access_rdv_list(): void
    {
        $this->get('/souscripteur/rdvs')
            ->assertRedirect('/souscripteur/login');
    }

    public function test_responsable_cannot_access_rdv_list(): void
    {
        $this->actingAs($this->createResponsable(), 'responsable')
            ->get('/souscripteur/rdvs')
            ->assertRedirect('/souscripteur/login');
    }

    public function test_agent_cannot_access_rdv_list(): void
    {
        $this->actingAs($this->createAgent(), 'agent')
            ->get('/souscripteur/rdvs')
            ->assertRedirect('/souscripteur/login');
    }

    public function test_admin_cannot_access_rdv_list(): void
    {
        $this->actingAs($this->createAdmin(), 'admin')
            ->get('/souscripteur/rdvs')
            ->assertRedirect('/souscripteur/login');
    }

    public function test_page_displays_only_authenticated_souscripteur_appointments(): void
    {
        $souscripteur = $this->createSouscripteur();
        $otherSouscripteur = $this->createSouscripteur([
            'code' => 'SUB002',
            'nin' => '222222222222222222',
        ]);
        $dr = Dr::create(['nom' => 'DR Alger']);

        $this->createRdv($souscripteur, $dr, [
            'motif' => 'Depot de dossier',
            'date' => '2026-09-15',
        ]);
        $this->createRdv($otherSouscripteur, $dr, [
            'motif' => 'Rendez-vous autre souscripteur',
            'date' => '2026-09-20',
        ]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->get('/souscripteur/rdvs')
            ->assertOk()
            ->assertSee('Depot de dossier')
            ->assertDontSee('Rendez-vous autre souscripteur');
    }

    public function test_dr_name_motif_date_and_french_status_label_are_displayed(): void
    {
        $souscripteur = $this->createSouscripteur();
        $dr = Dr::create(['nom' => 'DR Alger']);

        $this->createRdv($souscripteur, $dr, [
            'motif' => 'Depot de dossier',
            'date' => '2026-09-15',
            'statut' => Rdv::STATUT_RDV_PRIS,
        ]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->get('/souscripteur/rdvs')
            ->assertOk()
            ->assertSee('DR Alger')
            ->assertSee('Depot de dossier')
            ->assertSee('2026-09-15')
            ->assertSee('RDV pris');
    }

    public function test_empty_state_message_is_displayed_when_no_rdv_exists(): void
    {
        $souscripteur = $this->createSouscripteur();

        $this->actingAs($souscripteur, 'souscripteur')
            ->get('/souscripteur/rdvs')
            ->assertOk()
            ->assertSee('Aucun rendez-vous trouvé.');
    }

    public function test_appointments_are_ordered_by_newest_date_first(): void
    {
        $souscripteur = $this->createSouscripteur();
        $dr = Dr::create(['nom' => 'DR Alger']);

        $this->createRdv($souscripteur, $dr, [
            'motif' => 'Ancien rendez-vous',
            'date' => '2026-09-01',
        ]);
        $this->createRdv($souscripteur, $dr, [
            'motif' => 'Nouveau rendez-vous',
            'date' => '2026-10-01',
        ]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->get('/souscripteur/rdvs')
            ->assertOk()
            ->assertSeeInOrder([
                'Nouveau rendez-vous',
                'Ancien rendez-vous',
            ]);
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

    private function createResponsable(): Responsable
    {
        $dr = Dr::firstOrCreate(['nom' => 'DR Alger']);

        return Responsable::create([
            'email' => 'responsable@aadl.test',
            'password' => Hash::make('password'),
            'dr_id' => $dr->id,
        ]);
    }

    private function createAgent(): Agent
    {
        $dr = Dr::firstOrCreate(['nom' => 'DR Alger']);

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
