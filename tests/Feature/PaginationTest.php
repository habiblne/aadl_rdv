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

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_souscripteur_rdv_list_is_paginated(): void
    {
        $souscripteur = $this->createSouscripteur();

        for ($i = 1; $i <= 16; $i++) {
            $this->createRdv($souscripteur, $souscripteur->dr, [
                'date' => '2026-09-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($souscripteur, 'souscripteur')
            ->get('/souscripteur/rdvs')
            ->assertOk()
            ->assertSee('page=2', false);
    }

    public function test_responsable_rdv_list_is_paginated_and_preserves_date_filter(): void
    {
        $dr = Dr::create(['nom' => 'Direction Regionale Test']);
        $responsable = $this->createResponsable($dr);

        for ($i = 1; $i <= 16; $i++) {
            $this->createRdv($this->createSouscripteur([
                'code' => 'RESP'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'nin' => str_pad((string) $i, 18, '2', STR_PAD_LEFT),
                'dr_id' => $dr->id,
            ]), $dr, ['date' => '2026-09-15']);
        }

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs?date=2026-09-15')
            ->assertOk()
            ->assertSee('date=2026-09-15', false)
            ->assertSee('page=2', false);
    }

    public function test_admin_souscripteur_list_is_paginated_and_preserves_search(): void
    {
        $admin = $this->createAdmin();

        for ($i = 1; $i <= 16; $i++) {
            $this->createSouscripteur([
                'code' => 'PAGE-SUB-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'nin' => str_pad((string) $i, 18, '3', STR_PAD_LEFT),
            ]);
        }

        $this->actingAs($admin, 'admin')
            ->get('/admin/souscripteurs?q=PAGE-SUB')
            ->assertOk()
            ->assertSee('q=PAGE-SUB', false)
            ->assertSee('page=2', false);
    }

    public function test_admin_responsable_list_is_paginated_and_preserves_search(): void
    {
        $admin = $this->createAdmin();
        $dr = Dr::create(['nom' => 'Direction Regionale Test']);

        for ($i = 1; $i <= 16; $i++) {
            $this->createResponsable($dr, 'page-responsable-'.$i.'@aadl.test');
        }

        $this->actingAs($admin, 'admin')
            ->get('/admin/responsables?q=page-responsable')
            ->assertOk()
            ->assertSee('q=page-responsable', false)
            ->assertSee('page=2', false);
    }

    public function test_admin_agent_list_is_paginated_and_preserves_search(): void
    {
        $admin = $this->createAdmin();
        $dr = Dr::create(['nom' => 'Direction Regionale Test']);

        for ($i = 1; $i <= 16; $i++) {
            $this->createAgent($dr, 'page-agent-'.$i.'@aadl.test');
        }

        $this->actingAs($admin, 'admin')
            ->get('/admin/agents?q=page-agent')
            ->assertOk()
            ->assertSee('q=page-agent', false)
            ->assertSee('page=2', false);
    }

    public function test_admin_admin_list_is_paginated_and_preserves_search(): void
    {
        $admin = $this->createAdmin();

        for ($i = 1; $i <= 16; $i++) {
            $this->createAdmin('page-admin-'.$i.'@aadl.test');
        }

        $this->actingAs($admin, 'admin')
            ->get('/admin/admins?q=page-admin')
            ->assertOk()
            ->assertSee('q=page-admin', false)
            ->assertSee('page=2', false);
    }

    public function test_admin_rdv_list_is_paginated_and_preserves_filters(): void
    {
        $admin = $this->createAdmin();
        $dr = Dr::create(['nom' => 'Direction Regionale Test']);

        for ($i = 1; $i <= 16; $i++) {
            $this->createRdv($this->createSouscripteur([
                'code' => 'RDV'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'nin' => str_pad((string) $i, 18, '4', STR_PAD_LEFT),
                'dr_id' => $dr->id,
            ]), $dr, [
                'date' => '2026-09-15',
                'statut' => Rdv::STATUT_RDV_ACCEPTE,
            ]);
        }

        $this->actingAs($admin, 'admin')
            ->get("/admin/rdvs?date=2026-09-15&dr_id={$dr->id}&statut=1")
            ->assertOk()
            ->assertSee('date=2026-09-15', false)
            ->assertSee("dr_id={$dr->id}", false)
            ->assertSee('statut=1', false)
            ->assertSee('page=2', false);
    }

    private function createRdv(Souscripteur $souscripteur, Dr $dr, array $attributes = []): Rdv
    {
        return Rdv::create(array_merge([
            'souscripteur_id' => $souscripteur->id,
            'dr_id' => $dr->id,
            'date' => '2026-09-15',
            'motif' => 'Pagination test',
            'statut' => Rdv::STATUT_RDV_PRIS,
        ], $attributes));
    }

    private function createSouscripteur(array $attributes = []): Souscripteur
    {
        $drId = array_key_exists('dr_id', $attributes)
            ? $attributes['dr_id']
            : Dr::firstOrCreate(['nom' => 'Alger Est'])->id;

        return Souscripteur::create(array_merge([
            'code' => 'SUB001',
            'nom' => 'Test',
            'prenom' => 'Souscripteur',
            'nin' => '111111111111111111',
            'prop' => 'F3',
            'wil' => '16',
            'dr_id' => $drId,
            'password' => Hash::make('password'),
        ], $attributes));
    }

    private function createResponsable(Dr $dr, string $email = 'responsable@aadl.test'): Responsable
    {
        return Responsable::create([
            'email' => $email,
            'password' => Hash::make('password'),
            'dr_id' => $dr->id,
        ]);
    }

    private function createAgent(Dr $dr, string $email = 'agent@aadl.test'): Agent
    {
        return Agent::create([
            'email' => $email,
            'password' => Hash::make('password'),
            'dr_id' => $dr->id,
        ]);
    }

    private function createAdmin(string $email = 'admin@aadl.test'): Admin
    {
        return Admin::create([
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
    }
}
