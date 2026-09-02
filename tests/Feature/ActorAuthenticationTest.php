<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Agent;
use App\Models\Dr;
use App\Models\Responsable;
use App\Models\Souscripteur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ActorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_actor_login_page_loads(): void
    {
        foreach ($this->actors() as $actor => $config) {
            $this->get("/{$actor}/login")
                ->assertOk()
                ->assertSee($config['title']);
        }
    }

    public function test_each_actor_can_log_in_with_valid_credentials_and_is_redirected_to_own_dashboard(): void
    {
        $this->createActors();

        foreach ($this->actors() as $actor => $config) {
            $response = $this->post("/{$actor}/login", $config['credentials']);

            $this->assertAuthenticated($actor);
            $response->assertRedirect("/{$actor}/dashboard");

            auth($actor)->logout();
        }
    }

    public function test_invalid_credentials_are_rejected_for_each_actor(): void
    {
        $this->createActors();

        foreach ($this->actors() as $actor => $config) {
            $credentials = $config['credentials'];
            $credentials['password'] = 'wrong-password';

            $this->from("/{$actor}/login")
                ->post("/{$actor}/login", $credentials)
                ->assertRedirect("/{$actor}/login")
                ->assertSessionHasErrors($config['field']);

            $this->assertGuest($actor);
        }
    }

    public function test_login_attempts_are_rate_limited_for_each_actor(): void
    {
        $this->createActors();

        foreach ($this->actors() as $actor => $config) {
            $credentials = $config['credentials'];
            $credentials['password'] = 'wrong-password';

            for ($i = 0; $i < 5; $i++) {
                $this->from("/{$actor}/login")
                    ->post("/{$actor}/login", $credentials)
                    ->assertRedirect("/{$actor}/login")
                    ->assertSessionHasErrors($config['field']);
            }

            $response = $this->from("/{$actor}/login")
                ->post("/{$actor}/login", $credentials)
                ->assertRedirect("/{$actor}/login")
                ->assertSessionHasErrors($config['field']);

            $this->assertStringContainsString(
                'Too many login attempts',
                $response->baseResponse->getSession()->get('errors')->first($config['field'])
            );

            RateLimiter::clear($this->throttleKey($actor, $credentials[$config['field']]));
        }
    }

    public function test_each_dashboard_is_protected_and_redirects_guests_to_own_login_page(): void
    {
        foreach (array_keys($this->actors()) as $actor) {
            $this->get("/{$actor}/dashboard")
                ->assertRedirect("/{$actor}/login");
        }
    }

    public function test_each_actor_cannot_access_another_actor_dashboard(): void
    {
        $models = $this->createActors();

        foreach ($models as $actor => $model) {
            foreach (array_keys($this->actors()) as $targetActor) {
                if ($actor === $targetActor) {
                    continue;
                }

                $this->logoutActors();

                $this->actingAs($model, $actor)
                    ->get("/{$targetActor}/dashboard")
                    ->assertRedirect("/{$targetActor}/login");
            }
        }
    }

    public function test_each_actor_can_log_out(): void
    {
        $models = $this->createActors();

        foreach ($models as $actor => $model) {
            $this->actingAs($model, $actor)
                ->post("/{$actor}/logout")
                ->assertRedirect("/{$actor}/login");

            $this->assertGuest($actor);
        }
    }

    public function test_souscripteur_authenticates_using_code_not_email(): void
    {
        $this->createActors();

        $this->post('/souscripteur/login', [
            'email' => 'SUB001',
            'password' => 'password',
        ])->assertSessionHasErrors('code');

        $this->assertGuest('souscripteur');
    }

    public function test_agent_valid_login_redirects_to_agent_dashboard(): void
    {
        $this->createActors();

        $this->withSession(['url.intended' => '/souscripteur/login'])
            ->post('/agent/login', [
                'email' => 'agent@aadl.test',
                'password' => 'password',
            ])
            ->assertRedirect('/agent/dashboard');

        $this->assertAuthenticated('agent');
    }

    public function test_admin_valid_login_redirects_to_admin_dashboard(): void
    {
        $this->createActors();

        $this->withSession(['url.intended' => '/souscripteur/login'])
            ->post('/admin/login', [
                'email' => 'admin@aadl.test',
                'password' => 'password',
            ])
            ->assertRedirect('/admin/dashboard');

        $this->assertAuthenticated('admin');
    }

    public function test_agent_invalid_login_returns_to_agent_login(): void
    {
        $this->createActors();

        $this->from('/agent/login')
            ->post('/agent/login', [
                'email' => 'agent@aadl.test',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/agent/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('agent');
    }

    public function test_admin_invalid_login_returns_to_admin_login(): void
    {
        $this->createActors();

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'admin@aadl.test',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_guest_access_to_agent_dashboard_redirects_to_agent_login(): void
    {
        $this->get('/agent/dashboard')
            ->assertRedirect('/agent/login');
    }

    public function test_guest_access_to_admin_dashboard_redirects_to_admin_login(): void
    {
        $this->get('/admin/dashboard')
            ->assertRedirect('/admin/login');
    }

    public function test_agent_cannot_access_admin_dashboard(): void
    {
        $models = $this->createActors();

        $this->actingAs($models['agent'], 'agent')
            ->get('/admin/dashboard')
            ->assertRedirect('/admin/login');
    }

    public function test_admin_cannot_access_agent_dashboard(): void
    {
        $models = $this->createActors();

        $this->actingAs($models['admin'], 'admin')
            ->get('/agent/dashboard')
            ->assertRedirect('/agent/login');
    }

    private function createActors(): array
    {
        $dr = Dr::create(['nom' => 'DR Alger']);

        return [
            'souscripteur' => Souscripteur::create([
                'code' => 'SUB001',
                'nom' => 'Test',
                'prenom' => 'Souscripteur',
                'nin' => '111111111111111111',
                'prop' => 'F3',
                'wil' => 'Alger',
                'password' => Hash::make('password'),
            ]),
            'responsable' => Responsable::create([
                'email' => 'responsable@aadl.test',
                'password' => Hash::make('password'),
                'dr_id' => $dr->id,
            ]),
            'agent' => Agent::create([
                'email' => 'agent@aadl.test',
                'password' => Hash::make('password'),
                'dr_id' => $dr->id,
            ]),
            'admin' => Admin::create([
                'email' => 'admin@aadl.test',
                'password' => Hash::make('password'),
            ]),
        ];
    }

    private function logoutActors(): void
    {
        foreach (array_keys($this->actors()) as $actor) {
            auth($actor)->logout();
        }
    }

    private function actors(): array
    {
        return [
            'souscripteur' => [
                'title' => 'Connexion Souscripteur',
                'field' => 'code',
                'credentials' => [
                    'code' => 'SUB001',
                    'password' => 'password',
                ],
            ],
            'responsable' => [
                'title' => 'Connexion Responsable',
                'field' => 'email',
                'credentials' => [
                    'email' => 'responsable@aadl.test',
                    'password' => 'password',
                ],
            ],
            'agent' => [
                'title' => 'Connexion Agent',
                'field' => 'email',
                'credentials' => [
                    'email' => 'agent@aadl.test',
                    'password' => 'password',
                ],
            ],
            'admin' => [
                'title' => 'Connexion Admin',
                'field' => 'email',
                'credentials' => [
                    'email' => 'admin@aadl.test',
                    'password' => 'password',
                ],
            ],
        ];
    }

    private function throttleKey(string $actor, string $credential): string
    {
        return strtolower($actor.'|'.$credential.'|127.0.0.1');
    }
}
