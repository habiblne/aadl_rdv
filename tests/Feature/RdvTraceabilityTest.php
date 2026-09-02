<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Agent;
use App\Models\Dr;
use App\Models\Rdv;
use App\Models\Responsable;
use App\Models\Souscripteur;
use App\Support\RdvHashids;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RdvTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_responsable_acceptance_stores_responsable_id_and_accepted_at(): void
    {
        Carbon::setTestNow('2026-08-04 10:15:00');
        [$dr, $responsable, $souscripteur] = $this->baseActors();
        $rdv = $this->createRdv($souscripteur, $dr, Rdv::STATUT_RDV_PRIS);

        $this->actingAs($responsable, 'responsable')
            ->patch("/responsable/rdvs/{$rdv->id}/accepter")
            ->assertRedirect('/responsable/rdvs');

        $rdv->refresh();

        $this->assertSame($responsable->id, $rdv->accepted_by_responsable_id);
        $this->assertTrue($rdv->accepted_at->equalTo(Carbon::parse('2026-08-04 10:15:00')));
    }

    public function test_failed_acceptance_does_not_write_traceability(): void
    {
        [$dr, $responsable, $souscripteur] = $this->baseActors();
        $rdv = $this->createRdv($souscripteur, $dr, Rdv::STATUT_RDV_ACCEPTE);

        $this->actingAs($responsable, 'responsable')
            ->patch("/responsable/rdvs/{$rdv->id}/accepter")
            ->assertSessionHasErrors('rdv');

        $rdv->refresh();

        $this->assertNull($rdv->accepted_by_responsable_id);
        $this->assertNull($rdv->accepted_at);
    }

    public function test_agent_validation_stores_agent_id_and_validated_at(): void
    {
        Carbon::setTestNow('2026-08-04 11:20:00');
        [$dr, , $souscripteur, $agent] = $this->baseActors();
        $rdv = $this->createRdv($souscripteur, $dr, Rdv::STATUT_RDV_ACCEPTE);

        $this->actingAs($agent, 'agent')
            ->patch(route('agent.rdvs.valider', $this->hashid($rdv)))
            ->assertRedirect(route('agent.rdvs.verification', $this->hashid($rdv)));

        $rdv->refresh();

        $this->assertSame($agent->id, $rdv->validated_by_agent_id);
        $this->assertTrue($rdv->validated_at->equalTo(Carbon::parse('2026-08-04 11:20:00')));
    }

    public function test_failed_validation_does_not_write_traceability(): void
    {
        [$dr, , $souscripteur, $agent] = $this->baseActors();
        $rdv = $this->createRdv($souscripteur, $dr, Rdv::STATUT_RDV_PRIS);

        $this->actingAs($agent, 'agent')
            ->patch(route('agent.rdvs.valider', $this->hashid($rdv)))
            ->assertSessionHasErrors('rdv');

        $rdv->refresh();

        $this->assertNull($rdv->validated_by_agent_id);
        $this->assertNull($rdv->validated_at);
    }

    public function test_responsable_completion_stores_responsable_id_and_completed_at(): void
    {
        Carbon::setTestNow('2026-08-04 12:25:00');
        [$dr, $responsable, $souscripteur] = $this->baseActors();
        $rdv = $this->createRdv($souscripteur, $dr, Rdv::STATUT_RDV_VALIDE);

        $this->actingAs($responsable, 'responsable')
            ->patch("/responsable/rdvs/{$rdv->id}/completer")
            ->assertRedirect('/responsable/rdvs');

        $rdv->refresh();

        $this->assertSame($responsable->id, $rdv->completed_by_responsable_id);
        $this->assertTrue($rdv->completed_at->equalTo(Carbon::parse('2026-08-04 12:25:00')));
    }

    public function test_failed_completion_does_not_write_traceability(): void
    {
        [$dr, $responsable, $souscripteur] = $this->baseActors();
        $rdv = $this->createRdv($souscripteur, $dr, Rdv::STATUT_RDV_ACCEPTE);

        $this->actingAs($responsable, 'responsable')
            ->patch("/responsable/rdvs/{$rdv->id}/completer")
            ->assertSessionHasErrors('rdv');

        $rdv->refresh();

        $this->assertNull($rdv->completed_by_responsable_id);
        $this->assertNull($rdv->completed_at);
    }

    public function test_admin_can_see_traceability_actors_and_timestamps(): void
    {
        [$dr, $responsable, $souscripteur, $agent] = $this->baseActors();
        $admin = $this->createAdmin();
        $rdv = $this->createRdv($souscripteur, $dr, Rdv::STATUT_RDV_COMPLETE, [
            'accepted_by_responsable_id' => $responsable->id,
            'accepted_at' => '2026-08-04 10:15:00',
            'validated_by_agent_id' => $agent->id,
            'validated_at' => '2026-08-04 11:20:00',
            'completed_by_responsable_id' => $responsable->id,
            'completed_at' => '2026-08-04 12:25:00',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs')
            ->assertOk()
            ->assertSee('Accepté par: '.$responsable->email)
            ->assertSee('Date et heure d’acceptation: '.$rdv->accepted_at->format('Y-m-d H:i'))
            ->assertSee('Validé par: '.$agent->email)
            ->assertSee('Date et heure de validation: '.$rdv->validated_at->format('Y-m-d H:i'))
            ->assertSee('Complété par: '.$responsable->email)
            ->assertSee('Date et heure de complétion: '.$rdv->completed_at->format('Y-m-d H:i'));
    }

    public function test_admin_sees_clear_empty_labels_for_missing_actions(): void
    {
        [$dr, , $souscripteur] = $this->baseActors();
        $admin = $this->createAdmin();
        $this->createRdv($souscripteur, $dr, Rdv::STATUT_RDV_PRIS);

        $this->actingAs($admin, 'admin')
            ->get('/admin/rdvs')
            ->assertOk()
            ->assertSee('Non accepté')
            ->assertSee('Non validé')
            ->assertSee('Non complété');
    }

    public function test_traceability_is_not_exposed_in_souscripteur_responsable_agent_pages_or_qr_content(): void
    {
        [$dr, $responsable, $souscripteur, $agent] = $this->baseActors();
        $adminTrace = Responsable::create(['email' => 'trace-responsable@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);
        $agentTrace = Agent::create(['email' => 'trace-agent@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);
        $rdv = $this->createRdv($souscripteur, $dr, Rdv::STATUT_RDV_VALIDE, [
            'accepted_by_responsable_id' => $adminTrace->id,
            'accepted_at' => '2026-08-04 10:15:00',
            'validated_by_agent_id' => $agentTrace->id,
            'validated_at' => '2026-08-04 11:20:00',
        ]);

        $forbiddenText = [
            'trace-responsable@aadl.test',
            'trace-agent@aadl.test',
            'Accepté par',
            'Validé par',
            'Date et heure d’acceptation',
            'Date et heure de validation',
        ];

        foreach ([
            $this->actingAs($souscripteur, 'souscripteur')->get('/souscripteur/rdvs'),
            $this->actingAs($souscripteur, 'souscripteur')->get(route('souscripteur.rdvs.fiche', $this->hashid($rdv))),
            $this->actingAs($responsable, 'responsable')->get('/responsable/rdvs'),
            $this->actingAs($agent, 'agent')->get(route('agent.rdvs.verification', $this->hashid($rdv))),
        ] as $response) {
            $response->assertOk();

            foreach ($forbiddenText as $text) {
                $response->assertDontSee($text);
            }
        }

        $sheet = $this->actingAs($souscripteur, 'souscripteur')
            ->get(route('souscripteur.rdvs.fiche', $this->hashid($rdv)));

        $sheet->assertDontSee('trace-responsable@aadl.test');
        $sheet->assertDontSee('trace-agent@aadl.test');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function baseActors(): array
    {
        $dr = Dr::create(['nom' => 'Direction Régionale Alger']);
        $responsable = Responsable::create(['email' => 'responsable@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);
        $souscripteur = Souscripteur::create(['code' => 'SUB001', 'nom' => 'Test', 'prenom' => 'Souscripteur', 'nin' => '111111111111111111', 'prop' => 'F3', 'wil' => 'Alger', 'password' => Hash::make('password')]);
        $agent = Agent::create(['email' => 'agent@aadl.test', 'password' => Hash::make('password'), 'dr_id' => $dr->id]);

        return [$dr, $responsable, $souscripteur, $agent];
    }

    private function createAdmin(): Admin
    {
        return Admin::create(['email' => 'admin@aadl.test', 'password' => Hash::make('password')]);
    }

    private function createRdv(Souscripteur $souscripteur, Dr $dr, int $statut, array $attributes = []): Rdv
    {
        return Rdv::create(array_merge([
            'souscripteur_id' => $souscripteur->id,
            'dr_id' => $dr->id,
            'date' => '2026-08-10',
            'motif' => 'Contrôle dossier',
            'statut' => $statut,
        ], $attributes));
    }

    private function hashid(Rdv $rdv): string
    {
        return app(RdvHashids::class)->encode($rdv);
    }
}
