<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Agent;
use App\Models\Dr;
use App\Models\Responsable;
use App\Models\Souscripteur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_and_reset_responsable_password(): void
    {
        [$admin, $dr, $responsable] = $this->responsableFixture();
        $oldHash = $responsable->password;

        $this->actingAs($admin, 'admin')
            ->get("/admin/responsables/{$responsable->id}/mot-de-passe")
            ->assertOk()
            ->assertSee('Réinitialiser le mot de passe du responsable')
            ->assertSee($responsable->email);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/responsables/{$responsable->id}/mot-de-passe", [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'email' => 'changed@aadl.test',
                'dr_id' => 999999,
                'role' => 'admin',
            ])
            ->assertRedirect('/admin/responsables')
            ->assertSessionHas('status', 'Le mot de passe du responsable a été réinitialisé.');

        $responsable->refresh();

        $this->assertSame('responsable@aadl.test', $responsable->email);
        $this->assertSame($dr->id, $responsable->dr_id);
        $this->assertNotSame($oldHash, $responsable->password);
        $this->assertTrue(Hash::check('new-password', $responsable->password));
        $this->assertFalse(Auth::guard('responsable')->attempt(['email' => $responsable->email, 'password' => 'old-password']));
        $this->assertTrue(Auth::guard('responsable')->attempt(['email' => $responsable->email, 'password' => 'new-password']));
    }

    public function test_admin_can_open_and_reset_agent_password(): void
    {
        [$admin, $dr, $agent] = $this->agentFixture();
        $oldHash = $agent->password;

        $this->actingAs($admin, 'admin')
            ->get("/admin/agents/{$agent->id}/mot-de-passe")
            ->assertOk()
            ->assertSee('Réinitialiser le mot de passe de l’agent')
            ->assertSee($agent->email);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/agents/{$agent->id}/mot-de-passe", [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'email' => 'changed@aadl.test',
                'dr_id' => 999999,
                'role' => 'admin',
            ])
            ->assertRedirect('/admin/agents')
            ->assertSessionHas('status', 'Le mot de passe de l’agent a été réinitialisé.');

        $agent->refresh();

        $this->assertSame('agent@aadl.test', $agent->email);
        $this->assertSame($dr->id, $agent->dr_id);
        $this->assertNotSame($oldHash, $agent->password);
        $this->assertTrue(Hash::check('new-password', $agent->password));
        $this->assertFalse(Auth::guard('agent')->attempt(['email' => $agent->email, 'password' => 'old-password']));
        $this->assertTrue(Auth::guard('agent')->attempt(['email' => $agent->email, 'password' => 'new-password']));
    }

    public function test_admin_can_open_and_reset_another_admin_password(): void
    {
        $admin = $this->createAdmin('admin@aadl.test');
        $otherAdmin = $this->createAdmin('other-admin@aadl.test');
        $oldHash = $otherAdmin->password;

        $this->actingAs($admin, 'admin')
            ->get("/admin/admins/{$otherAdmin->id}/mot-de-passe")
            ->assertOk()
            ->assertSee('Réinitialiser le mot de passe de l’administrateur')
            ->assertSee($otherAdmin->email);

        $this->actingAs($admin, 'admin')
            ->patch("/admin/admins/{$otherAdmin->id}/mot-de-passe", [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'email' => 'changed@aadl.test',
                'role' => 'agent',
            ])
            ->assertRedirect('/admin/admins')
            ->assertSessionHas('status', 'Le mot de passe de l’administrateur a été réinitialisé.');

        $otherAdmin->refresh();

        $this->assertSame('other-admin@aadl.test', $otherAdmin->email);
        $this->assertNotSame($oldHash, $otherAdmin->password);
        $this->assertTrue(Hash::check('new-password', $otherAdmin->password));
        $this->assertFalse(Auth::guard('admin')->attempt(['email' => $otherAdmin->email, 'password' => 'old-password']));
        $this->assertTrue(Auth::guard('admin')->attempt(['email' => $otherAdmin->email, 'password' => 'new-password']));
    }

    public function test_admin_can_reset_own_password_and_remain_authenticated(): void
    {
        $admin = $this->createAdmin('admin@aadl.test');

        $this->actingAs($admin, 'admin')
            ->patch("/admin/admins/{$admin->id}/mot-de-passe", [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect('/admin/admins');

        $this->assertAuthenticatedAs($admin->refresh(), 'admin');
        $this->assertFalse(Auth::guard('admin')->attempt(['email' => $admin->email, 'password' => 'old-password']));
        $this->assertTrue(Hash::check('new-password', $admin->password));
    }

    public function test_password_confirmation_and_minimum_length_are_required_and_passwords_are_not_repopulated(): void
    {
        [$admin, , $responsable] = $this->responsableFixture();

        $this->actingAs($admin, 'admin')
            ->from("/admin/responsables/{$responsable->id}/mot-de-passe")
            ->patch("/admin/responsables/{$responsable->id}/mot-de-passe", [
                'password' => 'new-password',
            ])
            ->assertRedirect("/admin/responsables/{$responsable->id}/mot-de-passe")
            ->assertSessionHasErrors('password')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');

        $this->actingAs($admin, 'admin')
            ->from("/admin/responsables/{$responsable->id}/mot-de-passe")
            ->patch("/admin/responsables/{$responsable->id}/mot-de-passe", [
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertRedirect("/admin/responsables/{$responsable->id}/mot-de-passe")
            ->assertSessionHasErrors('password')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');
    }

    public function test_guest_and_other_actors_cannot_access_password_reset_routes(): void
    {
        [$admin, $dr, $responsable] = $this->responsableFixture();
        $agent = Agent::create(['email' => 'agent@aadl.test', 'password' => Hash::make('old-password'), 'dr_id' => $dr->id]);
        $otherAdmin = $this->createAdmin('other-admin@aadl.test');
        $souscripteur = Souscripteur::create(['code' => 'SUB001', 'nom' => 'Test', 'prenom' => 'Souscripteur', 'nin' => '111111111111111111', 'prop' => 'F3', 'wil' => 'Alger', 'password' => Hash::make('old-password')]);

        foreach ($this->passwordRoutes($responsable, $agent, $otherAdmin) as [$method, $uri]) {
            $this->{$method}($uri)->assertRedirect('/admin/login');
        }

        foreach ([
            ['guard' => 'souscripteur', 'user' => $souscripteur],
            ['guard' => 'responsable', 'user' => $responsable],
            ['guard' => 'agent', 'user' => $agent],
        ] as $actor) {
            foreach ($this->passwordRoutes($responsable, $agent, $otherAdmin) as [$method, $uri]) {
                $this->actingAs($actor['user'], $actor['guard'])
                    ->{$method}($uri)
                    ->assertRedirect('/admin/login');
            }
        }

        $this->assertDatabaseHas('admins', ['id' => $admin->id]);
    }

    public function test_list_password_reset_buttons_follow_confirmed_scope(): void
    {
        [$admin, $dr, $responsable] = $this->responsableFixture();
        $agent = Agent::create(['email' => 'agent@aadl.test', 'password' => Hash::make('old-password'), 'dr_id' => $dr->id]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/souscripteurs')
            ->assertOk()
            ->assertDontSee('Réinitialiser le mot de passe');

        $this->actingAs($admin, 'admin')
            ->get('/admin/responsables')
            ->assertOk()
            ->assertSee(route('admin.responsables.password.edit', $responsable), false);

        $this->actingAs($admin, 'admin')
            ->get('/admin/agents')
            ->assertOk()
            ->assertSee(route('admin.agents.password.edit', $agent), false);

        $this->actingAs($admin, 'admin')
            ->get('/admin/admins')
            ->assertOk()
            ->assertSee(route('admin.admins.password.edit', $admin), false);
    }

    private function responsableFixture(): array
    {
        $admin = $this->createAdmin('admin@aadl.test');
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);
        $responsable = Responsable::create(['email' => 'responsable@aadl.test', 'password' => Hash::make('old-password'), 'dr_id' => $dr->id]);

        return [$admin, $dr, $responsable];
    }

    private function agentFixture(): array
    {
        $admin = $this->createAdmin('admin@aadl.test');
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);
        $agent = Agent::create(['email' => 'agent@aadl.test', 'password' => Hash::make('old-password'), 'dr_id' => $dr->id]);

        return [$admin, $dr, $agent];
    }

    private function createAdmin(string $email): Admin
    {
        return Admin::create(['email' => $email, 'password' => Hash::make('old-password')]);
    }

    private function passwordRoutes(Responsable $responsable, Agent $agent, Admin $admin): array
    {
        return [
            ['get', "/admin/responsables/{$responsable->id}/mot-de-passe"],
            ['patch', "/admin/responsables/{$responsable->id}/mot-de-passe"],
            ['get', "/admin/agents/{$agent->id}/mot-de-passe"],
            ['patch', "/admin/agents/{$agent->id}/mot-de-passe"],
            ['get', "/admin/admins/{$admin->id}/mot-de-passe"],
            ['patch', "/admin/admins/{$admin->id}/mot-de-passe"],
        ];
    }
}
