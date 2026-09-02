<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Agent;
use App\Models\Dr;
use App\Models\Responsable;
use App\Models\Souscripteur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SouscripteurProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_souscripteur_can_open_profile_page(): void
    {
        $souscripteur = $this->createSouscripteur();

        $this->actingAs($souscripteur, 'souscripteur')
            ->get('/souscripteur/profil')
            ->assertOk()
            ->assertSee('Mes informations');
    }

    public function test_guest_cannot_access_profile_page(): void
    {
        $this->get('/souscripteur/profil')
            ->assertRedirect('/souscripteur/login');
    }

    public function test_responsable_cannot_access_profile_page(): void
    {
        $responsable = $this->createResponsable();

        $this->actingAs($responsable, 'responsable')
            ->get('/souscripteur/profil')
            ->assertRedirect('/souscripteur/login');
    }

    public function test_agent_cannot_access_profile_page(): void
    {
        $agent = $this->createAgent();

        $this->actingAs($agent, 'agent')
            ->get('/souscripteur/profil')
            ->assertRedirect('/souscripteur/login');
    }

    public function test_admin_cannot_access_profile_page(): void
    {
        $admin = Admin::create([
            'email' => 'admin@aadl.test',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/souscripteur/profil')
            ->assertRedirect('/souscripteur/login');
    }

    public function test_profile_page_displays_authenticated_souscripteur_data(): void
    {
        $souscripteur = $this->createSouscripteur();

        $this->actingAs($souscripteur, 'souscripteur')
            ->get('/souscripteur/profil')
            ->assertOk()
            ->assertSee('SUB001')
            ->assertSee('Test')
            ->assertSee('Souscripteur')
            ->assertSee('111111111111111111')
            ->assertSee('F3')
            ->assertSee('Alger');
    }

    public function test_profile_page_does_not_expose_another_souscripteur_data(): void
    {
        $souscripteur = $this->createSouscripteur();

        Souscripteur::create([
            'code' => 'SUB002',
            'nom' => 'Autre',
            'prenom' => 'Personne',
            'nin' => '222222222222222222',
            'prop' => 'F4',
            'wil' => 'Oran',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->get('/souscripteur/profil')
            ->assertOk()
            ->assertDontSee('SUB002')
            ->assertDontSee('Autre')
            ->assertDontSee('Personne')
            ->assertDontSee('222222222222222222')
            ->assertDontSee('F4')
            ->assertDontSee('Oran');
    }

    private function createSouscripteur(): Souscripteur
    {
        return Souscripteur::create([
            'code' => 'SUB001',
            'nom' => 'Test',
            'prenom' => 'Souscripteur',
            'nin' => '111111111111111111',
            'prop' => 'F3',
            'wil' => 'Alger',
            'password' => Hash::make('password'),
        ]);
    }

    private function createResponsable(): Responsable
    {
        $dr = Dr::create(['nom' => 'DR Alger']);

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
}
