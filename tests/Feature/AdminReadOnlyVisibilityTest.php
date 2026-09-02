<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Agent;
use App\Models\Dr;
use App\Models\Rdv;
use App\Models\Responsable;
use App\Models\Souscripteur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminReadOnlyVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_access_every_admin_list_page(): void
    {
        $admin = $this->createAdmin();

        foreach ($this->adminPages() as $uri) {
            $this->actingAs($admin, 'admin')->get($uri)->assertOk();
        }
    }

    public function test_guest_is_redirected_to_admin_login_for_admin_pages(): void
    {
        foreach ($this->adminPages() as $uri) {
            $this->get($uri)->assertRedirect('/admin/login');
        }
    }

    public function test_other_actors_cannot_access_admin_pages(): void
    {
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);
        $actors = [
            ['guard' => 'souscripteur', 'user' => $this->createSouscripteur(['code' => 'SUB001', 'nin' => '111111111111111111'])],
            ['guard' => 'responsable', 'user' => Responsable::create(['email' => 'responsable@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id])],
            ['guard' => 'agent', 'user' => Agent::create(['email' => 'agent@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id])],
        ];

        foreach ($actors as $actor) {
            foreach ($this->adminPages() as $uri) {
                $this->actingAs($actor['user'], $actor['guard'])
                    ->get($uri)
                    ->assertRedirect('/admin/login');
            }
        }
    }

    public function test_souscripteur_list_displays_only_expected_non_sensitive_fields(): void
    {
        $admin = $this->createAdmin();
        $hash = Hash::make('password');
        $this->createSouscripteur([
            'code' => 'SUB100',
            'nom' => 'NomTest',
            'prenom' => 'PrenomTest',
            'nin' => '100000000000000000',
            'prop' => 'F4',
            'wil' => 'Oran',
            'password' => $hash,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/souscripteurs');

        $response
            ->assertOk()
            ->assertSee('SUB100')
            ->assertSee('NomTest')
            ->assertSee('PrenomTest')
            ->assertSee('100000000000000000')
            ->assertSee('F4')
            ->assertSee('Oran')
            ->assertDontSee($hash, false)
            ->assertDontSee('Mot de passe');
    }

    public function test_souscripteur_search_works(): void
    {
        $admin = $this->createAdmin();
        $this->createSouscripteur(['code' => 'FOUND001', 'nom' => 'Cherche', 'nin' => '200000000000000000']);
        $this->createSouscripteur(['code' => 'HIDDEN001', 'nom' => 'Autre', 'nin' => '300000000000000000']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/souscripteurs?q=FOUND')
            ->assertOk()
            ->assertSee('FOUND001')
            ->assertDontSee('HIDDEN001');
    }

    public function test_responsable_list_displays_dr_and_search_works(): void
    {
        $admin = $this->createAdmin();
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);
        Responsable::create(['email' => 'visible-responsable@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);
        Responsable::create(['email' => 'hidden-responsable@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/responsables?q=visible')
            ->assertOk()
            ->assertSee('visible-responsable@aadl.test')
            ->assertSee('Direction Régionale Alger')
            ->assertDontSee('hidden-responsable@aadl.test');
    }

    public function test_agent_list_displays_dr_and_search_works(): void
    {
        $admin = $this->createAdmin();
        $dr = Dr::create(['nom' => 'Direction Générale AADL']);
        Agent::create(['email' => 'visible-agent@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);
        Agent::create(['email' => 'hidden-agent@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/agents?q=visible')
            ->assertOk()
            ->assertSee('visible-agent@aadl.test')
            ->assertSee('Direction Générale AADL')
            ->assertDontSee('hidden-agent@aadl.test');
    }

    public function test_admin_list_displays_emails_without_password_data_and_search_works(): void
    {
        $admin = $this->createAdmin(['email' => 'main-admin@aadl.test']);
        $hash = Hash::make('password');
        Admin::create(['email' => 'visible-admin@aadl.test', 'password' => $hash]);
        Admin::create(['email' => 'hidden-admin@aadl.test', 'password' => Hash::make('password')]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/admins?q=visible')
            ->assertOk()
            ->assertSee('visible-admin@aadl.test')
            ->assertDontSee('hidden-admin@aadl.test')
            ->assertDontSee($hash, false)
            ->assertDontSee('Mot de passe');
    }

    public function test_rdv_list_displays_appointments_from_multiple_drs(): void
    {
        $admin = $this->createAdmin();
        [$drAlger, $drGeneral] = $this->createDirections();
        $souscripteur = $this->createSouscripteur();

        Rdv::create(['souscripteur_id' => $souscripteur->id, 'dr_id' => $drAlger->id, 'date' => '2026-08-10', 'motif' => 'Motif Alger', 'statut' => Rdv::STATUT_RDV_PRIS]);
        Rdv::create(['souscripteur_id' => $souscripteur->id, 'dr_id' => $drGeneral->id, 'date' => '2026-08-11', 'motif' => 'Motif Général', 'statut' => Rdv::STATUT_RDV_ACCEPTE]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs')
            ->assertOk()
            ->assertSee('Motif Alger')
            ->assertSee('Motif Général')
            ->assertSee('Direction Régionale Alger')
            ->assertSee('Direction Générale AADL')
            ->assertSee('SUB001')
            ->assertSee('Test')
            ->assertSee('Souscripteur')
            ->assertSee('111111111111111111');
    }

    public function test_rdv_date_filter_works(): void
    {
        $admin = $this->createAdmin();
        [$dr] = $this->createDirections();
        $souscripteur = $this->createSouscripteur();

        Rdv::create(['souscripteur_id' => $souscripteur->id, 'dr_id' => $dr->id, 'date' => '2026-08-10', 'motif' => 'Matching Date', 'statut' => 0]);
        Rdv::create(['souscripteur_id' => $souscripteur->id, 'dr_id' => $dr->id, 'date' => '2026-08-11', 'motif' => 'Other Date', 'statut' => 0]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs?date=2026-08-10')
            ->assertOk()
            ->assertSee('Matching Date')
            ->assertDontSee('Other Date');
    }

    public function test_rdv_dr_filter_works(): void
    {
        $admin = $this->createAdmin();
        [$drAlger, $drGeneral] = $this->createDirections();
        $souscripteur = $this->createSouscripteur();

        Rdv::create(['souscripteur_id' => $souscripteur->id, 'dr_id' => $drAlger->id, 'date' => '2026-08-10', 'motif' => 'DR Alger Match', 'statut' => 0]);
        Rdv::create(['souscripteur_id' => $souscripteur->id, 'dr_id' => $drGeneral->id, 'date' => '2026-08-10', 'motif' => 'DR General Hidden', 'statut' => 0]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs?dr_id='.$drAlger->id)
            ->assertOk()
            ->assertSee('DR Alger Match')
            ->assertDontSee('DR General Hidden');
    }

    public function test_rdv_status_filter_works(): void
    {
        $admin = $this->createAdmin();
        [$dr] = $this->createDirections();
        $souscripteur = $this->createSouscripteur();

        Rdv::create(['souscripteur_id' => $souscripteur->id, 'dr_id' => $dr->id, 'date' => '2026-08-10', 'motif' => 'Accepted Match', 'statut' => Rdv::STATUT_RDV_ACCEPTE]);
        Rdv::create(['souscripteur_id' => $souscripteur->id, 'dr_id' => $dr->id, 'date' => '2026-08-10', 'motif' => 'Taken Hidden', 'statut' => Rdv::STATUT_RDV_PRIS]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs?statut=1')
            ->assertOk()
            ->assertSee('Accepted Match')
            ->assertSee('RDV accepté')
            ->assertDontSee('Taken Hidden');
    }

    public function test_invalid_rdv_filters_are_rejected_safely(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs?date=bad-date')
            ->assertSessionHasErrors('date');

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs?dr_id=999999')
            ->assertSessionHasErrors('dr_id');

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs?statut=9')
            ->assertSessionHasErrors('statut');
    }

    public function test_no_password_hash_appears_in_rendered_admin_pages(): void
    {
        $admin = $this->createAdmin();
        $hash = Hash::make('password');
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);
        $souscripteur = $this->createSouscripteur(['password' => $hash]);
        Responsable::create(['email' => 'responsable@aadl.test', 'password' => $hash, 'dr_id' => $dr->id]);
        Agent::create(['email' => 'agent@aadl.test', 'password' => $hash, 'dr_id' => $dr->id]);
        Admin::create(['email' => 'other-admin@aadl.test', 'password' => $hash]);
        Rdv::create(['souscripteur_id' => $souscripteur->id, 'dr_id' => $dr->id, 'date' => '2026-08-10', 'motif' => 'Contrôle', 'statut' => 0]);

        foreach ($this->adminPages() as $uri) {
            $this->actingAs($admin, 'admin')
                ->get($uri)
                ->assertOk()
                ->assertDontSee($hash, false);
        }
    }

    private function adminPages(): array
    {
        return [
            '/admin/souscripteurs',
            '/admin/responsables',
            '/admin/agents',
            '/admin/admins',
            '/admin/rdvs',
        ];
    }

    private function createAdmin(array $attributes = []): Admin
    {
        return Admin::create(array_merge([
            'email' => 'admin@aadl.test',
            'password' => Hash::make('password'),
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
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ], $attributes));
    }

    private function createDirections(): array
    {
        return [
            Dr::create(['nom' => 'Direction Régionale Alger']),
            Dr::create(['nom' => 'Direction Générale AADL']),
        ];
    }
}
