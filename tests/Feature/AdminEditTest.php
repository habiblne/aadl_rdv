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

class AdminEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_responsable_edit_form(): void
    {
        [$admin, $dr, $responsable] = $this->responsableFixture();

        $this->actingAs($admin, 'admin')
            ->get("/admin/responsables/{$responsable->id}/edit")
            ->assertOk()
            ->assertSee('Modifier le responsable')
            ->assertSee($responsable->email)
            ->assertSee($dr->nom);
    }

    public function test_admin_can_update_responsable_email_and_dr_without_changing_password(): void
    {
        [$admin, , $responsable] = $this->responsableFixture();
        $newDr = Dr::create(['nom' => 'Direction Générale AADL']);
        $originalPassword = $responsable->password;

        $this->actingAs($admin, 'admin')
            ->patch("/admin/responsables/{$responsable->id}", [
                'email' => 'updated-responsable@aadl.test',
                'dr_id' => $newDr->id,
                'password' => 'hacked-password',
                'role' => 'admin',
            ])
            ->assertRedirect('/admin/responsables')
            ->assertSessionHas('status', 'Le responsable a été modifié.');

        $responsable->refresh();

        $this->assertSame('updated-responsable@aadl.test', $responsable->email);
        $this->assertSame($newDr->id, $responsable->dr_id);
        $this->assertSame($originalPassword, $responsable->password);
    }

    public function test_duplicate_responsable_email_and_invalid_dr_are_rejected(): void
    {
        [$admin, $dr, $responsable] = $this->responsableFixture();
        Responsable::create(['email' => 'duplicate@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/responsables/{$responsable->id}", [
                'email' => 'duplicate@aadl.test',
                'dr_id' => $dr->id,
            ])
            ->assertSessionHasErrors('email');

        $this->actingAs($admin, 'admin')
            ->patch("/admin/responsables/{$responsable->id}", [
                'email' => 'valid@aadl.test',
                'dr_id' => 999999,
            ])
            ->assertSessionHasErrors('dr_id');
    }

    public function test_admin_can_open_agent_edit_form(): void
    {
        [$admin, $dr, $agent] = $this->agentFixture();

        $this->actingAs($admin, 'admin')
            ->get("/admin/agents/{$agent->id}/edit")
            ->assertOk()
            ->assertSee('Modifier l’agent')
            ->assertSee($agent->email)
            ->assertSee($dr->nom);
    }

    public function test_admin_can_update_agent_email_and_dr_without_changing_password(): void
    {
        [$admin, , $agent] = $this->agentFixture();
        $newDr = Dr::create(['nom' => 'Direction Générale AADL']);
        $originalPassword = $agent->password;

        $this->actingAs($admin, 'admin')
            ->patch("/admin/agents/{$agent->id}", [
                'email' => 'updated-agent@aadl.test',
                'dr_id' => $newDr->id,
                'password' => 'hacked-password',
                'role' => 'admin',
            ])
            ->assertRedirect('/admin/agents')
            ->assertSessionHas('status', 'L’agent a été modifié.');

        $agent->refresh();

        $this->assertSame('updated-agent@aadl.test', $agent->email);
        $this->assertSame($newDr->id, $agent->dr_id);
        $this->assertSame($originalPassword, $agent->password);
    }

    public function test_duplicate_agent_email_and_invalid_dr_are_rejected(): void
    {
        [$admin, $dr, $agent] = $this->agentFixture();
        Agent::create(['email' => 'duplicate@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/agents/{$agent->id}", [
                'email' => 'duplicate@aadl.test',
                'dr_id' => $dr->id,
            ])
            ->assertSessionHasErrors('email');

        $this->actingAs($admin, 'admin')
            ->patch("/admin/agents/{$agent->id}", [
                'email' => 'valid-agent@aadl.test',
                'dr_id' => 999999,
            ])
            ->assertSessionHasErrors('dr_id');
    }

    public function test_admin_can_open_admin_edit_form(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = Admin::create(['email' => 'other-admin@aadl.test', 'password' => Hash::make('password')]);

        $this->actingAs($admin, 'admin')
            ->get("/admin/admins/{$otherAdmin->id}/edit")
            ->assertOk()
            ->assertSee('Modifier l’administrateur')
            ->assertSee($otherAdmin->email);
    }

    public function test_admin_can_update_another_admin_email_without_changing_password(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = Admin::create(['email' => 'other-admin@aadl.test', 'password' => Hash::make('password')]);
        $originalPassword = $otherAdmin->password;

        $this->actingAs($admin, 'admin')
            ->patch("/admin/admins/{$otherAdmin->id}", [
                'email' => 'updated-admin@aadl.test',
                'password' => 'hacked-password',
                'role' => 'agent',
            ])
            ->assertRedirect('/admin/admins')
            ->assertSessionHas('status', 'L’administrateur a été modifié.');

        $otherAdmin->refresh();

        $this->assertSame('updated-admin@aadl.test', $otherAdmin->email);
        $this->assertSame($originalPassword, $otherAdmin->password);
    }

    public function test_admin_can_update_own_email_and_remain_authenticated(): void
    {
        $admin = $this->createAdmin();
        $originalPassword = $admin->password;

        $this->actingAs($admin, 'admin')
            ->patch("/admin/admins/{$admin->id}", ['email' => 'renamed-admin@aadl.test'])
            ->assertRedirect('/admin/admins');

        $this->assertAuthenticatedAs($admin->refresh(), 'admin');
        $this->assertSame('renamed-admin@aadl.test', $admin->email);
        $this->assertSame($originalPassword, $admin->password);
    }

    public function test_duplicate_admin_email_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = Admin::create(['email' => 'other-admin@aadl.test', 'password' => Hash::make('password')]);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/admins/{$otherAdmin->id}", ['email' => $admin->email])
            ->assertSessionHasErrors('email');
    }

    public function test_guest_and_other_actors_cannot_access_any_edit_route(): void
    {
        [$admin, $dr, $responsable] = $this->responsableFixture();
        $agent = Agent::create(['email' => 'agent@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);
        $otherAdmin = Admin::create(['email' => 'other-admin@aadl.test', 'password' => Hash::make('password')]);
        $souscripteur = Souscripteur::create(['code' => 'SUB001', 'nom' => 'Test', 'prenom' => 'Souscripteur', 'nin' => '111111111111111111', 'prop' => 'F3', 'wil' => 'Alger', 'password' => Hash::make('password')]);

        foreach ($this->editRoutes($responsable, $agent, $otherAdmin) as [$method, $uri]) {
            $this->{$method}($uri)->assertRedirect('/admin/login');
        }

        foreach ([
            ['guard' => 'souscripteur', 'user' => $souscripteur],
            ['guard' => 'responsable', 'user' => $responsable],
            ['guard' => 'agent', 'user' => $agent],
        ] as $actor) {
            foreach ($this->editRoutes($responsable, $agent, $otherAdmin) as [$method, $uri]) {
                $this->actingAs($actor['user'], $actor['guard'])
                    ->{$method}($uri)
                    ->assertRedirect('/admin/login');
            }
        }

        $this->actingAs($admin, 'admin')->get('/admin/dashboard')->assertOk();
    }

    public function test_list_edit_buttons_follow_confirmed_scope(): void
    {
        [$admin, $dr, $responsable] = $this->responsableFixture();
        $agent = Agent::create(['email' => 'agent@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/souscripteurs')
            ->assertOk()
            ->assertDontSee('Modifier');

        $this->actingAs($admin, 'admin')
            ->get('/admin/responsables')
            ->assertOk()
            ->assertSee(route('admin.responsables.edit', $responsable), false);

        $this->actingAs($admin, 'admin')
            ->get('/admin/agents')
            ->assertOk()
            ->assertSee(route('admin.agents.edit', $agent), false);

        $this->actingAs($admin, 'admin')
            ->get('/admin/admins')
            ->assertOk()
            ->assertSee(route('admin.admins.edit', $admin), false);
    }

    public function test_existing_rdv_traceability_remains_accessible_after_actor_edits(): void
    {
        [$admin, $dr, $responsable] = $this->responsableFixture();
        $agent = Agent::create(['email' => 'agent@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);
        $souscripteur = Souscripteur::create(['code' => 'SUB001', 'nom' => 'Test', 'prenom' => 'Souscripteur', 'nin' => '111111111111111111', 'prop' => 'F3', 'wil' => 'Alger', 'password' => Hash::make('password')]);
        $rdv = Rdv::create([
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
        $newDr = Dr::create(['nom' => 'Direction Générale AADL']);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/responsables/{$responsable->id}", [
                'email' => 'edited-responsable@aadl.test',
                'dr_id' => $newDr->id,
            ]);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/agents/{$agent->id}", [
                'email' => 'edited-agent@aadl.test',
                'dr_id' => $newDr->id,
            ]);

        $rdv->refresh();

        $this->assertSame($responsable->id, $rdv->accepted_by_responsable_id);
        $this->assertSame($agent->id, $rdv->validated_by_agent_id);
        $this->assertSame($responsable->id, $rdv->completed_by_responsable_id);

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs')
            ->assertOk()
            ->assertSee('edited-responsable@aadl.test')
            ->assertSee('edited-agent@aadl.test');
    }

    private function responsableFixture(): array
    {
        $admin = $this->createAdmin();
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);
        $responsable = Responsable::create(['email' => 'responsable@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);

        return [$admin, $dr, $responsable];
    }

    private function agentFixture(): array
    {
        $admin = $this->createAdmin();
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);
        $agent = Agent::create(['email' => 'agent@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);

        return [$admin, $dr, $agent];
    }

    private function createAdmin(): Admin
    {
        return Admin::create(['email' => 'admin@aadl.test', 'password' => Hash::make('password')]);
    }

    private function editRoutes(Responsable $responsable, Agent $agent, Admin $admin): array
    {
        return [
            ['get', "/admin/responsables/{$responsable->id}/edit"],
            ['patch', "/admin/responsables/{$responsable->id}"],
            ['get', "/admin/agents/{$agent->id}/edit"],
            ['patch', "/admin/agents/{$agent->id}"],
            ['get', "/admin/admins/{$admin->id}/edit"],
            ['patch', "/admin/admins/{$admin->id}"],
        ];
    }
}
