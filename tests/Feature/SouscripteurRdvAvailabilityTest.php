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

class SouscripteurRdvAvailabilityTest extends TestCase
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

    public function test_authenticated_souscripteur_can_request_full_dates_for_their_dr(): void
    {
        $souscripteur = $this->createSouscripteur();

        $this->actingAs($souscripteur, 'souscripteur')
            ->getJson('/souscripteur/rdvs/indisponibilites?dr_id='.$souscripteur->dr_id)
            ->assertOk()
            ->assertExactJson(['dates_completes' => []]);
    }

    public function test_guest_cannot_access_availability_endpoint(): void
    {
        $this->get('/souscripteur/rdvs/indisponibilites')
            ->assertRedirect('/souscripteur/login');
    }

    public function test_responsable_cannot_access_availability_endpoint(): void
    {
        $this->actingAs($this->createResponsable(), 'responsable')
            ->get('/souscripteur/rdvs/indisponibilites')
            ->assertRedirect('/souscripteur/login');
    }

    public function test_agent_cannot_access_availability_endpoint(): void
    {
        $this->actingAs($this->createAgent(), 'agent')
            ->get('/souscripteur/rdvs/indisponibilites')
            ->assertRedirect('/souscripteur/login');
    }

    public function test_admin_cannot_access_availability_endpoint(): void
    {
        $this->actingAs($this->createAdmin(), 'admin')
            ->get('/souscripteur/rdvs/indisponibilites')
            ->assertRedirect('/souscripteur/login');
    }

    public function test_other_dr_id_is_rejected_by_availability_endpoint(): void
    {
        $ownDr = $this->createDr('Alger Est');
        $otherDr = $this->createDr('Alger Ouest');
        $souscripteur = $this->createSouscripteur(['dr_id' => $ownDr->id]);
        $this->createCapacityAppointments($otherDr, '2026-08-10', 30);

        $this->actingAs($souscripteur, 'souscripteur')
            ->getJson('/souscripteur/rdvs/indisponibilites?dr_id='.$otherDr->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dr_id');
    }

    public function test_souscripteur_without_dr_gets_validation_error_from_availability_endpoint(): void
    {
        $generalDr = $this->createDr('Direction GÃ©nÃ©rale AADL');
        $souscripteur = $this->createSouscripteur(['dr_id' => null]);

        $this->actingAs($souscripteur, 'souscripteur')
            ->getJson('/souscripteur/rdvs/indisponibilites?dr_id='.$generalDr->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rdv');
    }

    public function test_direction_generale_id_is_allowed_by_availability_endpoint(): void
    {
        $generalDr = $this->createDr('Direction Générale AADL');
        $ownDr = $this->createDr('Oran');
        $souscripteur = $this->createSouscripteur(['dr_id' => $ownDr->id]);
        $this->createCapacityAppointments($generalDr, '2026-08-10', 30);

        $this->actingAs($souscripteur, 'souscripteur')
            ->getJson('/souscripteur/rdvs/indisponibilites?dr_id='.$generalDr->id)
            ->assertOk()
            ->assertJsonPath('dates_completes.0', '2026-08-10');
    }

    public function test_endpoint_returns_a_date_containing_thirty_rdvs_for_souscripteur_dr(): void
    {
        $souscripteur = $this->createSouscripteur();
        $this->createCapacityAppointments($souscripteur->dr, '2026-08-10', 30);

        $this->actingAs($souscripteur, 'souscripteur')
            ->getJson('/souscripteur/rdvs/indisponibilites?dr_id='.$souscripteur->dr_id)
            ->assertOk()
            ->assertJsonPath('dates_completes.0', '2026-08-10');
    }

    public function test_endpoint_does_not_return_a_date_containing_twenty_nine_rdvs(): void
    {
        $souscripteur = $this->createSouscripteur();
        $this->createCapacityAppointments($souscripteur->dr, '2026-08-10', 29);

        $this->actingAs($souscripteur, 'souscripteur')
            ->getJson('/souscripteur/rdvs/indisponibilites?dr_id='.$souscripteur->dr_id)
            ->assertOk()
            ->assertJsonMissing(['2026-08-10']);
    }

    public function test_endpoint_does_not_expose_souscripteur_or_rdv_personal_data(): void
    {
        $souscripteur = $this->createSouscripteur();
        $this->createCapacityAppointments($souscripteur->dr, '2026-08-10', 30);

        $response = $this->actingAs($souscripteur, 'souscripteur')
            ->getJson('/souscripteur/rdvs/indisponibilites?dr_id='.$souscripteur->dr_id)
            ->assertOk();

        $response->assertJsonStructure(['dates_completes']);
        $response->assertJsonMissing(['souscripteur_id']);
        $response->assertJsonMissing(['motif']);
        $response->assertJsonMissing(['nin']);
        $response->assertJsonMissing(['code']);
    }

    public function test_dates_before_today_plus_three_days_are_not_returned(): void
    {
        $souscripteur = $this->createSouscripteur();
        $this->createCapacityAppointments($souscripteur->dr, '2026-08-05', 30);
        $this->createCapacityAppointments($souscripteur->dr, '2026-08-06', 30);

        $this->actingAs($souscripteur, 'souscripteur')
            ->getJson('/souscripteur/rdvs/indisponibilites?dr_id='.$souscripteur->dr_id)
            ->assertOk()
            ->assertJsonMissing(['2026-08-05'])
            ->assertJsonPath('dates_completes.0', '2026-08-06');
    }

    public function test_appointment_form_contains_the_availability_endpoint_logic(): void
    {
        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->get('/souscripteur/rdvs/create')
            ->assertOk()
            ->assertSee('/souscripteur/rdvs/indisponibilites', false)
            ->assertSee('status.dataset.availabilityUrl', false)
            ->assertSee('fetch(url', false)
            ->assertSee('directionSelect.value', false)
            ->assertSee('dates_completes', false);
    }

    public function test_date_field_is_enabled_when_a_dr_is_attached(): void
    {
        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->get('/souscripteur/rdvs/create')
            ->assertOk()
            ->assertSee('id="date"', false)
            ->assertDontSee('id="date" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600 disabled:bg-slate-100 disabled:text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:disabled:bg-slate-800 dark:disabled:text-slate-500" type="date" name="date" value="" min="2026-08-06" required disabled', false);
    }

    public function test_frontend_clears_a_fully_booked_selected_date(): void
    {
        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->get('/souscripteur/rdvs/create')
            ->assertOk()
            ->assertSee("dateInput.value = ''", false)
            ->assertSee('Complet - 30 rendez-vous', false);
    }

    public function test_frontend_disables_submission_for_a_fully_booked_date(): void
    {
        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->get('/souscripteur/rdvs/create')
            ->assertOk()
            ->assertSee('submitButton.disabled', false)
            ->assertSee('fullDates.includes(dateInput.value)', false);
    }

    public function test_backend_still_rejects_the_thirty_first_appointment(): void
    {
        $souscripteur = $this->createSouscripteur();
        $this->createCapacityAppointments($souscripteur->dr, '2026-08-10', 30);

        $this->actingAs($souscripteur, 'souscripteur')
            ->from('/souscripteur/rdvs/create')
            ->post('/souscripteur/rdvs', [
                'dr_id' => $souscripteur->dr_id,
                'motif' => 'Depot de dossier',
                'date' => '2026-08-10',
            ])
            ->assertRedirect('/souscripteur/rdvs/create')
            ->assertSessionHasErrors('date');

        $this->assertDatabaseCount('rdvs', 30);
    }

    public function test_a_date_full_for_another_dr_remains_selectable_for_souscripteur_dr(): void
    {
        $ownDr = $this->createDr('Alger Est');
        $otherDr = $this->createDr('Alger Ouest');
        $souscripteur = $this->createSouscripteur(['dr_id' => $ownDr->id]);
        $this->createCapacityAppointments($otherDr, '2026-08-10', 30);

        $this->actingAs($souscripteur, 'souscripteur')
            ->getJson('/souscripteur/rdvs/indisponibilites?dr_id='.$ownDr->id)
            ->assertOk()
            ->assertExactJson(['dates_completes' => []]);
    }

    private function createCapacityAppointments(Dr $dr, string $date, int $count): void
    {
        $dateKey = str_replace('-', '', $date);

        for ($i = 1; $i <= $count; $i++) {
            $souscripteur = $this->createSouscripteur([
                'code' => 'AV'.$dateKey.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'nin' => str_pad($dateKey.(string) $i, 18, '8', STR_PAD_LEFT),
                'dr_id' => $dr->id,
            ]);

            Rdv::create([
                'souscripteur_id' => $souscripteur->id,
                'dr_id' => $dr->id,
                'motif' => 'Depot de dossier',
                'date' => $date,
                'statut' => Rdv::STATUT_RDV_PRIS,
            ]);
        }
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

        return Responsable::create([
            'email' => 'responsable@aadl.test',
            'password' => Hash::make('password'),
            'dr_id' => $dr->id,
        ]);
    }

    private function createAgent(): Agent
    {
        $dr = Dr::firstOrCreate(['nom' => 'Alger Est']);

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
