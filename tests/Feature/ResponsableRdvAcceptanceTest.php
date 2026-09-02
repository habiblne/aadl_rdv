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

class ResponsableRdvAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 1;

    public function test_authenticated_responsable_can_accept_status_zero_rdv_from_their_own_dr(): void
    {
        [$responsable, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_PRIS);

        $this->actingAs($responsable, 'responsable')
            ->patch("/responsable/rdvs/{$rdv->id}/accepter")
            ->assertRedirect('/responsable/rdvs')
            ->assertSessionHas('status', 'Le rendez-vous a été accepté.');

        $this->assertSame(Rdv::STATUT_RDV_ACCEPTE, $rdv->fresh()->statut);
    }

    public function test_status_changes_from_zero_to_one(): void
    {
        [$responsable, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_PRIS);

        $this->actingAs($responsable, 'responsable')
            ->patch("/responsable/rdvs/{$rdv->id}/accepter");

        $this->assertDatabaseHas('rdvs', [
            'id' => $rdv->id,
            'statut' => Rdv::STATUT_RDV_ACCEPTE,
        ]);
    }

    public function test_responsable_cannot_accept_rdv_from_another_dr(): void
    {
        $ownDr = Dr::create(['nom' => 'Direction Régionale Alger']);
        $otherDr = Dr::create(['nom' => 'Direction Générale AADL']);
        $responsable = $this->createResponsable($ownDr);
        $rdv = $this->createRdv($this->createSouscripteur(), $otherDr);

        $this->actingAs($responsable, 'responsable')
            ->patch("/responsable/rdvs/{$rdv->id}/accepter")
            ->assertNotFound();

        $this->assertSame(Rdv::STATUT_RDV_PRIS, $rdv->fresh()->statut);
    }

    public function test_souscripteur_cannot_use_acceptance_route(): void
    {
        [, $rdv] = $this->createResponsableWithRdv();

        $this->actingAs($this->createSouscripteur('SUB999', '999999999999999999'), 'souscripteur')
            ->patch("/responsable/rdvs/{$rdv->id}/accepter")
            ->assertRedirect('/responsable/login');
    }

    public function test_agent_cannot_use_acceptance_route(): void
    {
        [, $rdv] = $this->createResponsableWithRdv();

        $this->actingAs($this->createAgent(), 'agent')
            ->patch("/responsable/rdvs/{$rdv->id}/accepter")
            ->assertRedirect('/responsable/login');
    }

    public function test_admin_cannot_use_acceptance_route(): void
    {
        [, $rdv] = $this->createResponsableWithRdv();

        $this->actingAs($this->createAdmin(), 'admin')
            ->patch("/responsable/rdvs/{$rdv->id}/accepter")
            ->assertRedirect('/responsable/login');
    }

    public function test_guest_cannot_use_acceptance_route(): void
    {
        [, $rdv] = $this->createResponsableWithRdv();

        $this->patch("/responsable/rdvs/{$rdv->id}/accepter")
            ->assertRedirect('/responsable/login');
    }

    public function test_status_one_rdv_cannot_be_accepted_again(): void
    {
        $this->assertStatusCannotBeAccepted(Rdv::STATUT_RDV_ACCEPTE);
    }

    public function test_status_two_rdv_cannot_be_accepted(): void
    {
        $this->assertStatusCannotBeAccepted(Rdv::STATUT_RDV_VALIDE);
    }

    public function test_status_three_rdv_cannot_be_accepted(): void
    {
        $this->assertStatusCannotBeAccepted(Rdv::STATUT_RDV_COMPLETE);
    }

    public function test_accept_button_appears_only_for_status_zero(): void
    {
        [$responsable, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_PRIS);

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs')
            ->assertOk()
            ->assertSee('Accepter')
            ->assertSee(route('responsable.rdvs.accepter', $rdv), false);
    }

    public function test_accept_button_is_hidden_for_statuses_one_two_and_three(): void
    {
        foreach ([Rdv::STATUT_RDV_ACCEPTE, Rdv::STATUT_RDV_VALIDE, Rdv::STATUT_RDV_COMPLETE] as $status) {
            [$responsable, $rdv] = $this->createResponsableWithRdv($status);

            $this->actingAs($responsable, 'responsable')
                ->get('/responsable/rdvs')
                ->assertOk()
                ->assertDontSee('Accepter')
                ->assertDontSee(route('responsable.rdvs.accepter', $rdv), false);
        }
    }

    public function test_existing_date_filter_still_works(): void
    {
        $dr = Dr::firstOrCreate(['nom' => 'Direction Régionale Alger']);
        $responsable = $this->createResponsable($dr);
        $souscripteur = $this->createSouscripteur();
        $this->createRdv($souscripteur, $dr, ['motif' => 'RDV filtre', 'date' => '2026-09-15']);
        $this->createRdv($souscripteur, $dr, ['motif' => 'RDV hors filtre', 'date' => '2026-09-16']);

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs?date=2026-09-15')
            ->assertOk()
            ->assertSee('RDV filtre')
            ->assertDontSee('RDV hors filtre');
    }

    public function test_existing_dr_isolation_still_works(): void
    {
        $ownDr = Dr::create(['nom' => 'Direction Régionale Alger']);
        $otherDr = Dr::create(['nom' => 'Direction Générale AADL']);
        $responsable = $this->createResponsable($ownDr);
        $souscripteur = $this->createSouscripteur();
        $this->createRdv($souscripteur, $ownDr, ['motif' => 'RDV Alger']);
        $this->createRdv($souscripteur, $otherDr, ['motif' => 'RDV autre DR']);

        $this->actingAs($responsable, 'responsable')
            ->get('/responsable/rdvs')
            ->assertOk()
            ->assertSee('RDV Alger')
            ->assertDontSee('RDV autre DR');
    }

    public function test_update_is_safe_if_status_changes_before_submission(): void
    {
        [$responsable, $rdv] = $this->createResponsableWithRdv(Rdv::STATUT_RDV_PRIS);
        $rdv->update(['statut' => Rdv::STATUT_RDV_VALIDE]);

        $this->actingAs($responsable, 'responsable')
            ->patch("/responsable/rdvs/{$rdv->id}/accepter")
            ->assertRedirect('/responsable/rdvs')
            ->assertSessionHasErrors('rdv');

        $this->assertSame(Rdv::STATUT_RDV_VALIDE, $rdv->fresh()->statut);
    }

    private function assertStatusCannotBeAccepted(int $status): void
    {
        [$responsable, $rdv] = $this->createResponsableWithRdv($status);

        $this->actingAs($responsable, 'responsable')
            ->patch("/responsable/rdvs/{$rdv->id}/accepter")
            ->assertRedirect('/responsable/rdvs')
            ->assertSessionHasErrors('rdv');

        $this->assertSame($status, $rdv->fresh()->statut);
    }

    private function createResponsableWithRdv(int $status = Rdv::STATUT_RDV_PRIS): array
    {
        $dr = Dr::firstOrCreate(['nom' => 'Direction Régionale Alger']);
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
            'statut' => Rdv::STATUT_RDV_PRIS,
        ], $attributes));
    }

    private function createResponsable(Dr $dr): Responsable
    {
        $suffix = str_pad((string) $this->sequence++, 3, '0', STR_PAD_LEFT);

        return Responsable::create([
            'email' => "responsable{$suffix}@aadl.test",
            'password' => Hash::make('password'),
            'dr_id' => $dr->id,
        ]);
    }

    private function createSouscripteur(?string $code = null, ?string $nin = null): Souscripteur
    {
        $suffix = str_pad((string) $this->sequence++, 3, '0', STR_PAD_LEFT);

        return Souscripteur::create([
            'code' => $code ?? "SUB{$suffix}",
            'nom' => 'Test',
            'prenom' => 'Souscripteur',
            'nin' => $nin ?? str_pad($suffix, 18, '1', STR_PAD_LEFT),
            'prop' => 'F3',
            'wil' => 'Alger',
            'password' => Hash::make('password'),
        ]);
    }

    private function createAgent(): Agent
    {
        $dr = Dr::firstOrCreate(['nom' => 'Direction Régionale Alger']);

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
