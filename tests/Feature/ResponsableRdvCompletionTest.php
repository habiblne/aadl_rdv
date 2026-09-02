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

class ResponsableRdvCompletionTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 1;

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

    public function test_authenticated_responsable_can_complete_status_two_rdv_from_their_own_dr(): void
    {
        [$responsable, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_VALIDE);

        $this->actingAs($responsable, 'responsable')
            ->patch(route('responsable.rdvs.completer', $rdv))
            ->assertRedirect('/responsable/rdvs')
            ->assertSessionHas('status', 'Le rendez-vous a été complété.');

        $this->assertSame(Rdv::STATUT_RDV_COMPLETE, $rdv->fresh()->statut);
    }

    public function test_status_changes_from_two_to_three(): void
    {
        [$responsable, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_VALIDE);

        $this->actingAs($responsable, 'responsable')
            ->patch(route('responsable.rdvs.completer', $rdv));

        $this->assertDatabaseHas('rdvs', [
            'id' => $rdv->id,
            'statut' => Rdv::STATUT_RDV_COMPLETE,
        ]);
    }

    public function test_responsable_cannot_complete_rdv_from_another_dr(): void
    {
        $ownDr = $this->createDr('Direction Régionale Alger');
        $otherDr = $this->createDr('Direction Générale AADL');
        $responsable = $this->createResponsable($ownDr);
        $rdv = $this->createRdv($this->createSouscripteur(), $otherDr, [
            'statut' => Rdv::STATUT_RDV_VALIDE,
        ]);

        $this->actingAs($responsable, 'responsable')
            ->patch(route('responsable.rdvs.completer', $rdv))
            ->assertNotFound();

        $this->assertSame(Rdv::STATUT_RDV_VALIDE, $rdv->fresh()->statut);
    }

    public function test_souscripteur_cannot_use_completion_route(): void
    {
        [, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_VALIDE);

        $this->actingAs($this->createSouscripteur(), 'souscripteur')
            ->patch(route('responsable.rdvs.completer', $rdv))
            ->assertRedirect('/responsable/login');
    }

    public function test_agent_cannot_use_completion_route(): void
    {
        [$responsable, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_VALIDE);

        $this->actingAs($this->createAgent($responsable->dr), 'agent')
            ->patch(route('responsable.rdvs.completer', $rdv))
            ->assertRedirect('/responsable/login');
    }

    public function test_admin_cannot_use_completion_route(): void
    {
        [, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_VALIDE);

        $this->actingAs($this->createAdmin(), 'admin')
            ->patch(route('responsable.rdvs.completer', $rdv))
            ->assertRedirect('/responsable/login');
    }

    public function test_guest_cannot_use_completion_route(): void
    {
        [, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_VALIDE);

        $this->patch(route('responsable.rdvs.completer', $rdv))
            ->assertRedirect('/responsable/login');
    }

    public function test_status_zero_cannot_be_completed(): void
    {
        $this->assertStatusCannotBeCompleted(Rdv::STATUT_RDV_PRIS);
    }

    public function test_status_one_cannot_be_completed(): void
    {
        $this->assertStatusCannotBeCompleted(Rdv::STATUT_RDV_ACCEPTE);
    }

    public function test_status_three_cannot_be_completed_again(): void
    {
        $this->assertStatusCannotBeCompleted(Rdv::STATUT_RDV_COMPLETE);
    }

    public function test_completer_button_appears_only_for_status_two(): void
    {
        [$responsable, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_VALIDE);

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs')
            ->assertOk()
            ->assertSee('Compléter')
            ->assertSee(route('responsable.rdvs.completer', $rdv), false)
            ->assertDontSee(route('responsable.rdvs.accepter', $rdv), false);
    }

    public function test_accepter_button_appears_only_for_status_zero(): void
    {
        [$responsable, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_PRIS);

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs')
            ->assertOk()
            ->assertSee('Accepter')
            ->assertSee(route('responsable.rdvs.accepter', $rdv), false)
            ->assertDontSee(route('responsable.rdvs.completer', $rdv), false);
    }

    public function test_no_responsable_action_appears_for_statuses_one_and_three(): void
    {
        foreach ([Rdv::STATUT_RDV_ACCEPTE, Rdv::STATUT_RDV_COMPLETE] as $status) {
            [$responsable, $rdv] = $this->createResponsableWithRdv($status);

            $this->actingAs($responsable, 'responsable')
                ->get('/responsable/rdvs')
                ->assertOk()
                ->assertDontSee('Accepter')
                ->assertDontSee('Compléter')
                ->assertDontSee(route('responsable.rdvs.accepter', $rdv), false)
                ->assertDontSee(route('responsable.rdvs.completer', $rdv), false);
        }
    }

    public function test_conditional_update_protects_against_concurrent_status_changes(): void
    {
        [$responsable, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_VALIDE);
        $rdv->update(['statut' => Rdv::STATUT_RDV_ACCEPTE]);

        $this->actingAs($responsable, 'responsable')
            ->patch(route('responsable.rdvs.completer', $rdv))
            ->assertRedirect('/responsable/rdvs')
            ->assertSessionHasErrors('rdv');

        $this->assertSame(Rdv::STATUT_RDV_ACCEPTE, $rdv->fresh()->statut);
    }

    public function test_existing_dr_isolation_remains_valid(): void
    {
        $ownDr = $this->createDr('Direction Régionale Alger');
        $otherDr = $this->createDr('Direction Générale AADL');
        $responsable = $this->createResponsable($ownDr);
        $souscripteur = $this->createSouscripteur($ownDr);
        $this->createRdv($souscripteur, $ownDr, ['motif' => 'RDV Alger', 'statut' => Rdv::STATUT_RDV_VALIDE]);
        $this->createRdv($souscripteur, $otherDr, ['motif' => 'RDV autre DR', 'statut' => Rdv::STATUT_RDV_VALIDE]);

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs')
            ->assertOk()
            ->assertSee('RDV Alger')
            ->assertDontSee('RDV autre DR');
    }

    public function test_after_status_becomes_three_souscripteur_can_create_new_rdv(): void
    {
        $dr = $this->createDr();
        $responsable = $this->createResponsable($dr);
        $souscripteur = $this->createSouscripteur($dr);
        $rdv = $this->createRdv($souscripteur, $dr, ['statut' => Rdv::STATUT_RDV_VALIDE]);

        $this->actingAs($responsable, 'responsable')
            ->patch(route('responsable.rdvs.completer', $rdv));

        $this->actingAs($souscripteur, 'souscripteur')
            ->post('/souscripteur/rdvs', [
                'dr_id' => $dr->id,
                'motif' => 'Nouveau rendez-vous',
                'date' => '2026-08-06',
            ])
            ->assertRedirect('/souscripteur/dashboard');

        $this->assertDatabaseHas('rdvs', [
            'souscripteur_id' => $souscripteur->id,
            'motif' => 'Nouveau rendez-vous',
            'statut' => Rdv::STATUT_RDV_PRIS,
        ]);
    }

    private function assertStatusCannotBeCompleted(int $status): void
    {
        [$responsable, $rdv] = $this->createResponsableWithRdv($status);

        $this->actingAs($responsable, 'responsable')
            ->patch(route('responsable.rdvs.completer', $rdv))
            ->assertRedirect('/responsable/rdvs')
            ->assertSessionHasErrors('rdv');

        $this->assertSame($status, $rdv->fresh()->statut);
    }

    private function createResponsableWithRdv(int $status): array
    {
        $dr = $this->createDr();
        $responsable = $this->createResponsable($dr);
        $rdv = $this->createRdv($this->createSouscripteur(), $dr, ['statut' => $status]);

        return [$responsable, $rdv];
    }

    private function createRdv(Souscripteur $souscripteur, Dr $dr, array $attributes = []): Rdv
    {
        return Rdv::create(array_merge([
            'souscripteur_id' => $souscripteur->id,
            'dr_id' => $dr->id,
            'motif' => 'Depot de dossier',
            'date' => '2026-09-15',
            'statut' => Rdv::STATUT_RDV_VALIDE,
        ], $attributes));
    }

    private function createDr(string $nom = 'Direction Régionale Alger'): Dr
    {
        return Dr::create(['nom' => $nom.' '.$this->nextSuffix()]);
    }

    private function createResponsable(Dr $dr): Responsable
    {
        return Responsable::create([
            'email' => 'responsable'.$this->nextSuffix().'@aadl.test',
            'password' => Hash::make('password'),
            'dr_id' => $dr->id,
        ]);
    }

    private function createSouscripteur(?Dr $dr = null): Souscripteur
    {
        $suffix = $this->nextSuffix();
        $dr ??= $this->createDr();

        return Souscripteur::create([
            'code' => "SUB{$suffix}",
            'nom' => 'Test',
            'prenom' => 'Souscripteur',
            'nin' => str_pad($suffix, 18, '1', STR_PAD_LEFT),
            'prop' => 'F3',
            'wil' => 'Alger',
            'dr_id' => $dr->id,
            'password' => Hash::make('password'),
        ]);
    }

    private function createAgent(Dr $dr): Agent
    {
        return Agent::create([
            'email' => 'agent'.$this->nextSuffix().'@aadl.test',
            'password' => Hash::make('password'),
            'dr_id' => $dr->id,
        ]);
    }

    private function createAdmin(): Admin
    {
        return Admin::create([
            'email' => 'admin'.$this->nextSuffix().'@aadl.test',
            'password' => Hash::make('password'),
        ]);
    }

    private function nextSuffix(): string
    {
        return str_pad((string) $this->sequence++, 3, '0', STR_PAD_LEFT);
    }
}
