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

class AdminCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_responsable_creation_form(): void
    {
        $admin = $this->createAdmin();
        Dr::create(['nom' => 'Direction Régionale Alger']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/responsables/create')
            ->assertOk()
            ->assertSee('Ajouter un responsable')
            ->assertSee('Direction Régionale Alger');
    }

    public function test_admin_can_create_responsable_with_valid_data_and_password_is_hashed(): void
    {
        $admin = $this->createAdmin();
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);

        $this->actingAs($admin, 'admin')
            ->post('/admin/responsables', [
                'email' => 'new-responsable@aadl.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'dr_id' => $dr->id,
            ])
            ->assertRedirect('/admin/responsables')
            ->assertSessionHas('status', 'Le responsable a été ajouté.');

        $responsable = Responsable::where('email', 'new-responsable@aadl.test')->first();

        $this->assertNotNull($responsable);
        $this->assertSame($dr->id, $responsable->dr_id);
        $this->assertNotSame('password', $responsable->password);
        $this->assertTrue(Hash::check('password', $responsable->password));
    }

    public function test_responsable_requires_valid_existing_dr(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post('/admin/responsables', [
                'email' => 'new-responsable@aadl.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'dr_id' => 999999,
            ])
            ->assertSessionHasErrors('dr_id');
    }

    public function test_duplicate_responsable_email_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);
        Responsable::create(['email' => 'duplicate@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/responsables', [
                'email' => 'duplicate@aadl.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'dr_id' => $dr->id,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_open_agent_creation_form(): void
    {
        $admin = $this->createAdmin();
        Dr::create(['nom' => 'Direction Générale AADL']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/agents/create')
            ->assertOk()
            ->assertSee('Ajouter un agent')
            ->assertSee('Direction Générale AADL');
    }

    public function test_admin_can_create_agent_with_valid_data_and_password_is_hashed(): void
    {
        $admin = $this->createAdmin();
        $dr = Dr::create(['nom' => 'Direction Générale AADL']);

        $this->actingAs($admin, 'admin')
            ->post('/admin/agents', [
                'email' => 'new-agent@aadl.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'dr_id' => $dr->id,
                'role' => 'admin',
            ])
            ->assertRedirect('/admin/agents')
            ->assertSessionHas('status', 'L’agent a été ajouté.');

        $agent = Agent::where('email', 'new-agent@aadl.test')->first();

        $this->assertNotNull($agent);
        $this->assertSame($dr->id, $agent->dr_id);
        $this->assertDatabaseMissing('admins', ['email' => 'new-agent@aadl.test']);
        $this->assertNotSame('password', $agent->password);
        $this->assertTrue(Hash::check('password', $agent->password));
    }

    public function test_agent_requires_valid_existing_dr(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post('/admin/agents', [
                'email' => 'new-agent@aadl.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'dr_id' => 999999,
            ])
            ->assertSessionHasErrors('dr_id');
    }

    public function test_duplicate_agent_email_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $dr = Dr::create(['nom' => 'Direction Générale AADL']);
        Agent::create(['email' => 'duplicate@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/agents', [
                'email' => 'duplicate@aadl.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'dr_id' => $dr->id,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_open_admin_creation_form(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/admins/create')
            ->assertOk()
            ->assertSee('Ajouter un administrateur');
    }

    public function test_admin_can_create_another_admin_with_valid_data_and_password_is_hashed(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->post('/admin/admins', [
                'email' => 'new-admin@aadl.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'agent',
            ])
            ->assertRedirect('/admin/admins')
            ->assertSessionHas('status', 'L’administrateur a été ajouté.');

        $createdAdmin = Admin::where('email', 'new-admin@aadl.test')->first();

        $this->assertNotNull($createdAdmin);
        $this->assertDatabaseMissing('agents', ['email' => 'new-admin@aadl.test']);
        $this->assertNotSame('password', $createdAdmin->password);
        $this->assertTrue(Hash::check('password', $createdAdmin->password));
    }

    public function test_duplicate_admin_email_is_rejected(): void
    {
        $admin = $this->createAdmin();
        Admin::create(['email' => 'duplicate@aadl.test', 'password' => Hash::make('password')]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/admins', [
                'email' => 'duplicate@aadl.test',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_password_confirmation_is_required_for_all_creation_forms(): void
    {
        $admin = $this->createAdmin();
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);

        $requests = [
            ['/admin/responsables', ['email' => 'responsable@aadl.test', 'password' => 'password', 'dr_id' => $dr->id]],
            ['/admin/agents', ['email' => 'agent@aadl.test', 'password' => 'password', 'dr_id' => $dr->id]],
            ['/admin/admins', ['email' => 'other-admin@aadl.test', 'password' => 'password']],
        ];

        foreach ($requests as [$uri, $payload]) {
            $this->actingAs($admin, 'admin')
                ->post($uri, $payload)
                ->assertSessionHasErrors('password');
        }
    }

    public function test_guest_cannot_access_any_creation_route(): void
    {
        foreach ($this->creationRoutes() as [$method, $uri]) {
            $this->{$method}($uri)->assertRedirect('/admin/login');
        }
    }

    public function test_other_actors_cannot_access_any_creation_route(): void
    {
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);
        $actors = [
            ['guard' => 'souscripteur', 'user' => Souscripteur::create(['code' => 'SUB001', 'nom' => 'Test', 'prenom' => 'Souscripteur', 'nin' => '111111111111111111', 'prop' => 'F3', 'wil' => 'Alger', 'password' => Hash::make('password')])],
            ['guard' => 'responsable', 'user' => Responsable::create(['email' => 'responsable@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id])],
            ['guard' => 'agent', 'user' => Agent::create(['email' => 'agent@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id])],
        ];

        foreach ($actors as $actor) {
            foreach ($this->creationRoutes() as [$method, $uri]) {
                $this->actingAs($actor['user'], $actor['guard'])
                    ->{$method}($uri)
                    ->assertRedirect('/admin/login');
            }
        }
    }

    public function test_list_creation_buttons_follow_confirmed_scope(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/souscripteurs')
            ->assertOk()
            ->assertDontSee('Ajouter un souscripteur');

        $this->actingAs($admin, 'admin')
            ->get('/admin/responsables')
            ->assertOk()
            ->assertSee('Ajouter un responsable');

        $this->actingAs($admin, 'admin')
            ->get('/admin/agents')
            ->assertOk()
            ->assertSee('Ajouter un agent');

        $this->actingAs($admin, 'admin')
            ->get('/admin/admins')
            ->assertOk()
            ->assertSee('Ajouter un administrateur');
    }

    private function createAdmin(): Admin
    {
        return Admin::create([
            'email' => 'admin@aadl.test',
            'password' => Hash::make('password'),
        ]);
    }

    private function creationRoutes(): array
    {
        return [
            ['get', '/admin/responsables/create'],
            ['post', '/admin/responsables'],
            ['get', '/admin/agents/create'],
            ['post', '/admin/agents'],
            ['get', '/admin/admins/create'],
            ['post', '/admin/admins'],
        ];
    }
}
