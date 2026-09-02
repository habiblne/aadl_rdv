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

class SouscripteurRdvCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-03');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_authenticated_souscripteur_can_open_appointment_form(): void
    {
        $generalDr = $this->createDr('Direction Générale AADL');
        $dr = $this->createDr('Alger Est');
        $souscripteur = $this->createSouscripteur(['dr_id' => $dr->id]);
        $otherDr = $this->createDr('Alger Ouest');

        $this->actingAs($souscripteur, 'souscripteur')
            ->get('/souscripteur/rdvs/create')
            ->assertOk()
            ->assertSee('Prendre un rendez-vous')
            ->assertSee('Direction Générale AADL')
            ->assertSee('Alger Est')
            ->assertDontSee('Alger Ouest')
            ->assertSee('name="dr_id"', false);

        $this->assertSame('Direction Générale AADL', $generalDr->nom);
        $this->assertSame('Alger Ouest', $otherDr->nom);
    }

    public function test_guest_cannot_open_appointment_form(): void
    {
        $this->get('/souscripteur/rdvs/create')->assertRedirect('/souscripteur/login');
    }

    public function test_responsable_cannot_open_appointment_form(): void
    {
        $this->actingAs($this->createResponsable(), 'responsable')->get('/souscripteur/rdvs/create')->assertRedirect('/souscripteur/login');
    }

    public function test_agent_cannot_open_appointment_form(): void
    {
        $this->actingAs($this->createAgent(), 'agent')->get('/souscripteur/rdvs/create')->assertRedirect('/souscripteur/login');
    }

    public function test_admin_cannot_open_appointment_form(): void
    {
        $this->actingAs($this->createAdmin(), 'admin')->get('/souscripteur/rdvs/create')->assertRedirect('/souscripteur/login');
    }

    public function test_authenticated_souscripteur_can_create_rdv_with_valid_data(): void
    {
        $dr = $this->createDr('Alger Est');
        $souscripteur = $this->createSouscripteur(['dr_id' => $dr->id]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->post('/souscripteur/rdvs', $this->validPayload(['dr_id' => $dr->id]))
            ->assertRedirect('/souscripteur/dashboard')
            ->assertSessionHas('status', 'Rendez-vous cree avec succes.');

        $this->assertDatabaseHas('rdvs', [
            'souscripteur_id' => $souscripteur->id,
            'dr_id' => $dr->id,
            'motif' => 'Depot de dossier',
            'statut' => Rdv::STATUT_RDV_PRIS,
        ]);
        $this->assertSame('2026-08-06', Rdv::firstOrFail()->date->toDateString());
    }

    public function test_submitted_other_dr_id_is_rejected(): void
    {
        $ownDr = $this->createDr('Alger Est');
        $otherDr = $this->createDr('Alger Ouest');
        $souscripteur = $this->createSouscripteur(['dr_id' => $ownDr->id]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->from('/souscripteur/rdvs/create')
            ->post('/souscripteur/rdvs', $this->validPayload(['dr_id' => $otherDr->id]))
            ->assertRedirect('/souscripteur/rdvs/create')
            ->assertSessionHasErrors('dr_id');

        $this->assertDatabaseMissing('rdvs', ['souscripteur_id' => $souscripteur->id, 'dr_id' => $ownDr->id]);
        $this->assertDatabaseMissing('rdvs', ['souscripteur_id' => $souscripteur->id, 'dr_id' => $otherDr->id]);
    }

    public function test_authenticated_souscripteur_can_create_rdv_for_direction_generale(): void
    {
        $generalDr = $this->createDr('Direction Générale AADL');
        $ownDr = $this->createDr('Alger Ouest');
        $souscripteur = $this->createSouscripteur(['dr_id' => $ownDr->id]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->post('/souscripteur/rdvs', $this->validPayload(['dr_id' => $generalDr->id]))
            ->assertRedirect('/souscripteur/dashboard');

        $this->assertDatabaseHas('rdvs', ['souscripteur_id' => $souscripteur->id, 'dr_id' => $generalDr->id]);
        $this->assertDatabaseMissing('rdvs', ['souscripteur_id' => $souscripteur->id, 'dr_id' => $ownDr->id]);
    }

    public function test_souscripteur_without_dr_cannot_create_rdv(): void
    {
        $souscripteur = $this->createSouscripteur(['dr_id' => null]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->from('/souscripteur/rdvs/create')
            ->post('/souscripteur/rdvs', $this->validPayload())
            ->assertRedirect('/souscripteur/rdvs/create')
            ->assertSessionHasErrors('rdv');

        $this->assertDatabaseCount('rdvs', 0);
    }

    public function test_souscripteur_without_dr_can_open_form_with_clean_error(): void
    {
        $souscripteur = $this->createSouscripteur(['dr_id' => null]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->get('/souscripteur/rdvs/create')
            ->assertOk()
            ->assertSee('Aucune direction regionale n est rattachee a votre compte')
            ->assertSee('disabled', false);
    }

    public function test_date_today_is_rejected(): void
    {
        $this->assertDateRejected('2026-08-03');
    }

    public function test_date_tomorrow_is_rejected(): void
    {
        $this->assertDateRejected('2026-08-04');
    }

    public function test_date_in_two_days_is_rejected(): void
    {
        $this->assertDateRejected('2026-08-05');
    }

    public function test_date_in_three_days_is_accepted(): void
    {
        $souscripteur = $this->createSouscripteur();

        $this->actingAs($souscripteur, 'souscripteur')
            ->post('/souscripteur/rdvs', $this->validPayload(['date' => '2026-08-06']))
            ->assertRedirect('/souscripteur/dashboard');

        $this->assertDatabaseCount('rdvs', 1);
    }

    public function test_frontend_date_input_contains_correct_minimum_date(): void
    {
        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->get('/souscripteur/rdvs/create')
            ->assertOk()
            ->assertSee('min="2026-08-06"', false)
            ->assertSee('au moins 3 jours');
    }

    public function test_souscripteur_with_status_zero_appointment_cannot_create_another(): void
    {
        $this->assertActiveAppointmentBlocksCreation(Rdv::STATUT_RDV_PRIS);
    }

    public function test_souscripteur_with_status_one_appointment_cannot_create_another(): void
    {
        $this->assertActiveAppointmentBlocksCreation(Rdv::STATUT_RDV_ACCEPTE);
    }

    public function test_souscripteur_with_status_two_appointment_cannot_create_another(): void
    {
        $this->assertActiveAppointmentBlocksCreation(Rdv::STATUT_RDV_VALIDE);
    }

    public function test_souscripteur_with_only_completed_status_three_appointments_can_create_another(): void
    {
        $souscripteur = $this->createSouscripteur();
        $this->createRdv($souscripteur, $souscripteur->dr, ['statut' => Rdv::STATUT_RDV_COMPLETE]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->post('/souscripteur/rdvs', $this->validPayload(['date' => '2026-08-07']))
            ->assertRedirect('/souscripteur/dashboard');

        $this->assertDatabaseCount('rdvs', 2);
    }

    public function test_thirtieth_appointment_for_souscripteur_dr_date_is_allowed_if_count_before_creation_is_twenty_nine(): void
    {
        $souscripteur = $this->createSouscripteur();
        $this->createCapacityAppointments($souscripteur->dr, '2026-08-06', 29);

        $this->actingAs($souscripteur, 'souscripteur')
            ->post('/souscripteur/rdvs', $this->validPayload(['dr_id' => $souscripteur->dr_id]))
            ->assertRedirect('/souscripteur/dashboard');

        $this->assertDatabaseCount('rdvs', 30);
    }

    public function test_thirty_first_appointment_for_souscripteur_dr_date_is_rejected_if_count_is_already_thirty(): void
    {
        $souscripteur = $this->createSouscripteur();
        $this->createCapacityAppointments($souscripteur->dr, '2026-08-06', 30);

        $this->actingAs($souscripteur, 'souscripteur')
            ->from('/souscripteur/rdvs/create')
            ->post('/souscripteur/rdvs', $this->validPayload(['dr_id' => $souscripteur->dr_id]))
            ->assertRedirect('/souscripteur/rdvs/create')
            ->assertSessionHasErrors('date');

        $this->assertDatabaseCount('rdvs', 30);
    }

    public function test_capacity_in_another_dr_does_not_block_souscripteur_dr(): void
    {
        $ownDr = $this->createDr('Alger Est');
        $otherDr = $this->createDr('Alger Ouest');
        $souscripteur = $this->createSouscripteur(['dr_id' => $ownDr->id]);
        $this->createCapacityAppointments($otherDr, '2026-08-06', 30);

        $this->actingAs($souscripteur, 'souscripteur')
            ->post('/souscripteur/rdvs', $this->validPayload(['dr_id' => $ownDr->id]))
            ->assertRedirect('/souscripteur/dashboard');

        $this->assertDatabaseHas('rdvs', ['souscripteur_id' => $souscripteur->id, 'dr_id' => $ownDr->id]);
    }

    public function test_capacity_on_one_date_does_not_block_another_date(): void
    {
        $souscripteur = $this->createSouscripteur();
        $this->createCapacityAppointments($souscripteur->dr, '2026-08-06', 30);

        $this->actingAs($souscripteur, 'souscripteur')
            ->post('/souscripteur/rdvs', $this->validPayload(['dr_id' => $souscripteur->dr_id, 'date' => '2026-08-07']))
            ->assertRedirect('/souscripteur/dashboard');

        $this->assertDatabaseHas('rdvs', ['souscripteur_id' => $souscripteur->id]);
    }

    public function test_created_rdv_belongs_to_authenticated_souscripteur(): void
    {
        $souscripteur = $this->createSouscripteur();
        $this->actingAs($souscripteur, 'souscripteur')->post('/souscripteur/rdvs', $this->validPayload());

        $this->assertSame($souscripteur->id, Rdv::firstOrFail()->souscripteur_id);
    }

    public function test_created_rdv_has_status_zero(): void
    {
        $souscripteur = $this->createSouscripteur();
        $this->actingAs($souscripteur, 'souscripteur')->post('/souscripteur/rdvs', $this->validPayload());

        $this->assertSame(0, Rdv::firstOrFail()->statut);
    }

    public function test_missing_motif_is_rejected(): void
    {
        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->from('/souscripteur/rdvs/create')
            ->post('/souscripteur/rdvs', $this->validPayload(['motif' => null]))
            ->assertRedirect('/souscripteur/rdvs/create')
            ->assertSessionHasErrors('motif');

        $this->assertDatabaseCount('rdvs', 0);
    }

    public function test_motif_longer_than_255_characters_is_rejected(): void
    {
        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->from('/souscripteur/rdvs/create')
            ->post('/souscripteur/rdvs', $this->validPayload(['motif' => str_repeat('A', 256)]))
            ->assertRedirect('/souscripteur/rdvs/create')
            ->assertSessionHasErrors('motif');

        $this->assertDatabaseCount('rdvs', 0);
    }

    public function test_missing_date_is_rejected(): void
    {
        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->from('/souscripteur/rdvs/create')
            ->post('/souscripteur/rdvs', $this->validPayload(['date' => null]))
            ->assertRedirect('/souscripteur/rdvs/create')
            ->assertSessionHasErrors('date');

        $this->assertDatabaseCount('rdvs', 0);
    }

    public function test_submitted_souscripteur_id_is_ignored(): void
    {
        $souscripteur = $this->createSouscripteur();
        $otherSouscripteur = $this->createSouscripteur(['code' => 'SUB002', 'nin' => '222222222222222222']);

        $this->actingAs($souscripteur, 'souscripteur')
            ->post('/souscripteur/rdvs', $this->validPayload() + ['souscripteur_id' => $otherSouscripteur->id]);

        $this->assertDatabaseHas('rdvs', ['souscripteur_id' => $souscripteur->id]);
        $this->assertDatabaseMissing('rdvs', ['souscripteur_id' => $otherSouscripteur->id]);
    }

    public function test_another_actor_cannot_submit_creation_request(): void
    {
        foreach (['responsable' => $this->createResponsable(), 'agent' => $this->createAgent(), 'admin' => $this->createAdmin()] as $guard => $actor) {
            $this->logoutActors();
            $this->actingAs($actor, $guard)->post('/souscripteur/rdvs', $this->validPayload())->assertRedirect('/souscripteur/login');
        }

        $this->assertDatabaseCount('rdvs', 0);
    }

    private function assertDateRejected(string $date): void
    {
        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->from('/souscripteur/rdvs/create')
            ->post('/souscripteur/rdvs', $this->validPayload(['date' => $date]))
            ->assertRedirect('/souscripteur/rdvs/create')
            ->assertSessionHasErrors('date');

        $this->assertDatabaseCount('rdvs', 0);
    }

    private function assertActiveAppointmentBlocksCreation(int $status): void
    {
        $souscripteur = $this->createSouscripteur();
        $this->createRdv($souscripteur, $souscripteur->dr, ['statut' => $status]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->from('/souscripteur/rdvs/create')
            ->post('/souscripteur/rdvs', $this->validPayload(['date' => '2026-08-07']))
            ->assertRedirect('/souscripteur/rdvs/create')
            ->assertSessionHasErrors('rdv');

        $this->assertDatabaseCount('rdvs', 1);
    }

    private function createCapacityAppointments(Dr $dr, string $date, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $souscripteur = $this->createSouscripteur([
                'code' => 'CAP'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'nin' => str_pad((string) $i, 18, '9', STR_PAD_LEFT),
                'dr_id' => $dr->id,
            ]);
            $this->createRdv($souscripteur, $dr, ['date' => $date]);
        }
    }

    private function createRdv(Souscripteur $souscripteur, Dr $dr, array $attributes = []): Rdv
    {
        return Rdv::create(array_merge([
            'souscripteur_id' => $souscripteur->id,
            'dr_id' => $dr->id,
            'motif' => 'Depot de dossier',
            'date' => '2026-08-06',
            'statut' => Rdv::STATUT_RDV_PRIS,
        ], $attributes));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'dr_id' => Dr::firstOrCreate(['nom' => 'Direction Générale AADL'])->id,
            'motif' => 'Depot de dossier',
            'date' => '2026-08-06',
        ], $overrides);
    }

    private function createDr(string $nom = 'Alger Est'): Dr
    {
        return Dr::create(['nom' => $nom]);
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
            'wil' => 'Alger',
            'dr_id' => $drId,
            'password' => Hash::make('password'),
        ], $attributes));
    }

    private function createResponsable(): Responsable
    {
        $dr = Dr::firstOrCreate(['nom' => 'Alger Est']);
        return Responsable::create(['email' => 'responsable@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);
    }

    private function createAgent(): Agent
    {
        $dr = Dr::firstOrCreate(['nom' => 'Alger Est']);
        return Agent::create(['email' => 'agent@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);
    }

    private function createAdmin(): Admin
    {
        return Admin::create(['email' => 'admin@aadl.test', 'password' => Hash::make('password')]);
    }

    private function logoutActors(): void
    {
        foreach (['souscripteur', 'responsable', 'agent', 'admin'] as $guard) {
            auth($guard)->logout();
        }
    }
}
