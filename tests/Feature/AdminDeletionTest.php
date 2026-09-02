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

class AdminDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_responsable_without_deleting_rdvs_and_traceability_renders_safely(): void
    {
        [$admin, $dr, $responsable, $agent, $souscripteur] = $this->fixture();
        $rdv = $this->createTracedRdv($dr, $responsable, $agent, $souscripteur);

        $this->actingAs($admin, 'admin')
            ->delete("/admin/responsables/{$responsable->id}")
            ->assertRedirect('/admin/responsables')
            ->assertSessionHas('status', 'Le responsable a été supprimé.');

        $this->assertDatabaseMissing('responsables', ['id' => $responsable->id]);
        $this->assertDatabaseHas('rdvs', ['id' => $rdv->id]);

        $rdv->refresh();
        $this->assertNull($rdv->accepted_by_responsable_id);
        $this->assertNull($rdv->completed_by_responsable_id);

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs')
            ->assertOk()
            ->assertSee('Responsable supprimé')
            ->assertSee('Date et heure d’acceptation: 2026-08-04 10:00')
            ->assertSee('Date et heure de complétion: 2026-08-04 12:00');
    }

    public function test_admin_can_delete_agent_without_deleting_rdvs_and_traceability_renders_safely(): void
    {
        [$admin, $dr, $responsable, $agent, $souscripteur] = $this->fixture();
        $rdv = $this->createTracedRdv($dr, $responsable, $agent, $souscripteur);

        $this->actingAs($admin, 'admin')
            ->delete("/admin/agents/{$agent->id}")
            ->assertRedirect('/admin/agents')
            ->assertSessionHas('status', 'L’agent a été supprimé.');

        $this->assertDatabaseMissing('agents', ['id' => $agent->id]);
        $this->assertDatabaseHas('rdvs', ['id' => $rdv->id]);

        $rdv->refresh();
        $this->assertNull($rdv->validated_by_agent_id);

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs')
            ->assertOk()
            ->assertSee('Agent supprimé')
            ->assertSee('Date et heure de validation: 2026-08-04 11:00');
    }

    public function test_admin_can_delete_another_admin_when_more_than_one_admin_exists(): void
    {
        $admin = $this->createAdmin('admin@aadl.test');
        $otherAdmin = $this->createAdmin('other-admin@aadl.test');

        $this->actingAs($admin, 'admin')
            ->delete("/admin/admins/{$otherAdmin->id}")
            ->assertRedirect('/admin/admins')
            ->assertSessionHas('status', 'L’administrateur a été supprimé.');

        $this->assertDatabaseMissing('admins', ['id' => $otherAdmin->id]);
        $this->assertDatabaseHas('admins', ['id' => $admin->id]);
    }

    public function test_admin_cannot_delete_own_account_or_last_remaining_admin(): void
    {
        $admin = $this->createAdmin('admin@aadl.test');

        $this->actingAs($admin, 'admin')
            ->delete("/admin/admins/{$admin->id}")
            ->assertRedirect('/admin/admins')
            ->assertSessionHasErrors('admin');

        $this->assertDatabaseHas('admins', ['id' => $admin->id]);

        $otherAdmin = $this->createAdmin('other-admin@aadl.test');
        $admin->delete();

        $this->actingAs($otherAdmin, 'admin')
            ->delete("/admin/admins/{$otherAdmin->id}")
            ->assertRedirect('/admin/admins')
            ->assertSessionHasErrors('admin');

        $this->assertDatabaseHas('admins', ['id' => $otherAdmin->id]);
        $this->assertSame(1, Admin::count());
    }

    public function test_guest_and_other_actors_cannot_use_deletion_routes(): void
    {
        [$admin, $dr, $responsable, $agent, $souscripteur] = $this->fixture();
        $otherAdmin = $this->createAdmin('other-admin@aadl.test');

        foreach ($this->deletionRoutes($responsable, $agent, $otherAdmin) as $uri) {
            $this->delete($uri)->assertRedirect('/admin/login');
        }

        foreach ([
            ['guard' => 'souscripteur', 'user' => $souscripteur],
            ['guard' => 'responsable', 'user' => $responsable],
            ['guard' => 'agent', 'user' => $agent],
        ] as $actor) {
            foreach ($this->deletionRoutes($responsable, $agent, $otherAdmin) as $uri) {
                $this->actingAs($actor['user'], $actor['guard'])
                    ->delete($uri)
                    ->assertRedirect('/admin/login');
            }
        }

        $this->assertDatabaseHas('admins', ['id' => $admin->id]);
        $this->assertDatabaseHas('admins', ['id' => $otherAdmin->id]);
        $this->assertDatabaseHas('responsables', ['id' => $responsable->id]);
        $this->assertDatabaseHas('agents', ['id' => $agent->id]);
    }

    public function test_souscripteur_has_no_deletion_route_and_list_contains_no_delete_button(): void
    {
        [$admin] = $this->fixture();

        $this->actingAs($admin, 'admin')
            ->delete('/admin/souscripteurs/1')
            ->assertNotFound();

        $this->actingAs($admin, 'admin')
            ->get('/admin/souscripteurs')
            ->assertOk()
            ->assertDontSee('Supprimer');
    }

    public function test_authorized_lists_show_allowed_delete_buttons_and_current_admin_has_no_delete_button(): void
    {
        [$admin, , $responsable, $agent] = $this->fixture();
        $otherAdmin = $this->createAdmin('other-admin@aadl.test');

        $this->actingAs($admin, 'admin')
            ->get('/admin/responsables')
            ->assertOk()
            ->assertSee(route('admin.responsables.destroy', $responsable), false)
            ->assertSee('Supprimer');

        $this->actingAs($admin, 'admin')
            ->get('/admin/agents')
            ->assertOk()
            ->assertSee(route('admin.agents.destroy', $agent), false)
            ->assertSee('Supprimer');

        $this->actingAs($admin, 'admin')
            ->get('/admin/admins')
            ->assertOk()
            ->assertSee('action="'.route('admin.admins.destroy', $otherAdmin).'"', false)
            ->assertDontSee('action="'.route('admin.admins.destroy', $admin).'"', false);
    }

    public function test_traceability_action_timestamps_remain_visible_after_actor_deletion(): void
    {
        [$admin, $dr, $responsable, $agent, $souscripteur] = $this->fixture();
        $this->createTracedRdv($dr, $responsable, $agent, $souscripteur);

        $this->actingAs($admin, 'admin')->delete("/admin/responsables/{$responsable->id}");
        $this->actingAs($admin, 'admin')->delete("/admin/agents/{$agent->id}");

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs')
            ->assertOk()
            ->assertSee('Date et heure d’acceptation: 2026-08-04 10:00')
            ->assertSee('Date et heure de validation: 2026-08-04 11:00')
            ->assertSee('Date et heure de complétion: 2026-08-04 12:00');
    }

    private function fixture(): array
    {
        $admin = $this->createAdmin('admin@aadl.test');
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);
        $responsable = Responsable::create(['email' => 'responsable@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);
        $agent = Agent::create(['email' => 'agent@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);
        $souscripteur = Souscripteur::create(['code' => 'SUB001', 'nom' => 'Test', 'prenom' => 'Souscripteur', 'nin' => '111111111111111111', 'prop' => 'F3', 'wil' => 'Alger', 'password' => Hash::make('password')]);

        return [$admin, $dr, $responsable, $agent, $souscripteur];
    }

    private function createTracedRdv(Dr $dr, Responsable $responsable, Agent $agent, Souscripteur $souscripteur): Rdv
    {
        return Rdv::create([
            'souscripteur_id' => $souscripteur->id,
            'dr_id' => $dr->id,
            'date' => '2026-08-10',
            'motif' => 'Contrôle dossier',
            'statut' => Rdv::STATUT_RDV_COMPLETE,
            'accepted_by_responsable_id' => $responsable->id,
            'accepted_at' => '2026-08-04 10:00:00',
            'validated_by_agent_id' => $agent->id,
            'validated_at' => '2026-08-04 11:00:00',
            'completed_by_responsable_id' => $responsable->id,
            'completed_at' => '2026-08-04 12:00:00',
        ]);
    }

    private function createAdmin(string $email): Admin
    {
        return Admin::create(['email' => $email, 'password' => Hash::make('password')]);
    }

    private function deletionRoutes(Responsable $responsable, Agent $agent, Admin $admin): array
    {
        return [
            "/admin/responsables/{$responsable->id}",
            "/admin/agents/{$agent->id}",
            "/admin/admins/{$admin->id}",
        ];
    }
}
