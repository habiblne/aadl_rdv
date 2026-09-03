<?php

namespace Tests\Feature;

use App\Models\Dr;
use App\Models\Rdv;
use App\Models\Souscripteur;
use App\Models\Wilaya;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederDirectionMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_confirmed_directions_without_tlemcen_as_dr(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ($this->officialDirections() as $direction) {
            $this->assertDatabaseHas('drs', ['nom' => $direction]);
        }

        $this->assertDatabaseMissing('drs', ['nom' => 'Tlemcen']);
    }

    public function test_seeder_contains_full_official_wilaya_to_dr_mapping(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ($this->officialWilayaMappings() as $code => [$wilayaName, $drName]) {
            if ($drName === null) {
                $alger = Wilaya::where('code', $code)->firstOrFail();

                $this->assertSame($wilayaName, $alger->nom);
                $this->assertNull($alger->dr_id);

                continue;
            }

            $this->assertWilayaDr($code, $wilayaName, $drName);
        }
    }

    public function test_alger_stays_special_and_is_not_automatically_assigned_to_est_or_ouest(): void
    {
        $this->seed(DatabaseSeeder::class);

        $alger = Wilaya::where('code', '16')->firstOrFail();

        $this->assertSame('Alger', $alger->nom);
        $this->assertNull($alger->dr_id);
    }

    public function test_seeded_accounts_remain_assigned_to_alger_est_explicitly(): void
    {
        $this->seed(DatabaseSeeder::class);

        $algerEst = Dr::where('nom', 'Alger Est')->firstOrFail();
        $directionGenerale = Dr::where('nom', 'Direction Générale AADL')->firstOrFail();

        $this->assertDatabaseHas('souscripteurs', [
            'code' => 'SUB001',
            'wil' => '16',
            'dr_id' => $algerEst->id,
        ]);
        $this->assertDatabaseHas('responsables', [
            'email' => 'responsable@aadl.test',
            'dr_id' => $algerEst->id,
        ]);
        $this->assertDatabaseHas('agents', [
            'email' => 'agent@aadl.test',
            'dr_id' => $algerEst->id,
        ]);
        $this->assertDatabaseHas('responsables', [
            'email' => 'responsable.dg@aadl.test',
            'dr_id' => $directionGenerale->id,
        ]);
        $this->assertDatabaseHas('agents', [
            'email' => 'agent.dg@aadl.test',
            'dr_id' => $directionGenerale->id,
        ]);
    }

    public function test_seeded_dg_staff_can_process_dg_rdv_while_preserving_dr_isolation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $directionGenerale = Dr::where('nom', 'Direction Générale AADL')->firstOrFail();
        $algerEst = Dr::where('nom', 'Alger Est')->firstOrFail();
        $dgResponsable = \App\Models\Responsable::where('email', 'responsable.dg@aadl.test')->firstOrFail();
        $dgAgent = \App\Models\Agent::where('email', 'agent.dg@aadl.test')->firstOrFail();
        $algerResponsable = \App\Models\Responsable::where('email', 'responsable@aadl.test')->firstOrFail();
        $algerAgent = \App\Models\Agent::where('email', 'agent@aadl.test')->firstOrFail();
        $souscripteur = Souscripteur::where('code', 'SUB001')->firstOrFail();

        $rdv = Rdv::create([
            'souscripteur_id' => $souscripteur->id,
            'dr_id' => $directionGenerale->id,
            'date' => '2026-09-15',
            'motif' => 'DG processing test',
            'statut' => Rdv::STATUT_RDV_PRIS,
        ]);

        $this->actingAs($algerResponsable, 'responsable')
            ->patch("/responsable/rdvs/{$rdv->id}/accepter")
            ->assertNotFound();

        $this->actingAs($dgResponsable, 'responsable')
            ->patch("/responsable/rdvs/{$rdv->id}/accepter")
            ->assertRedirect('/responsable/rdvs');

        $this->assertSame(Rdv::STATUT_RDV_ACCEPTE, $rdv->fresh()->statut);

        $this->actingAs($algerAgent, 'agent')
            ->patch("/agent/rdvs/{$rdv->fresh()->hashid}/valider")
            ->assertNotFound();

        $this->actingAs($dgAgent, 'agent')
            ->patch("/agent/rdvs/{$rdv->fresh()->hashid}/valider")
            ->assertRedirect("/agent/rdvs/{$rdv->fresh()->hashid}/verification");

        $this->assertSame(Rdv::STATUT_RDV_VALIDE, $rdv->fresh()->statut);

        $this->actingAs($dgResponsable, 'responsable')
            ->patch("/responsable/rdvs/{$rdv->id}/completer")
            ->assertRedirect('/responsable/rdvs');

        $this->assertSame(Rdv::STATUT_RDV_COMPLETE, $rdv->fresh()->statut);
        $this->assertSame($algerEst->id, $souscripteur->dr_id);
    }

    public function test_local_seeded_pagination_preview_data_has_expected_counts_and_page_split(): void
    {
        $this->seed(DatabaseSeeder::class);

        $previewSouscripteurs = Souscripteur::where('code', 'like', 'PAGE%')->orderBy('code');
        $previewRdvs = Rdv::where('motif', 'Pagination preview')->orderBy('id');

        $this->assertSame(20, $previewSouscripteurs->count());
        $this->assertSame(20, $previewRdvs->count());
        $this->assertSame(15, $previewSouscripteurs->paginate(15, ['*'], 'page', 1)->count());
        $this->assertSame(5, $previewSouscripteurs->paginate(15, ['*'], 'page', 2)->count());
        $this->assertSame(15, $previewRdvs->paginate(15, ['*'], 'page', 1)->count());
        $this->assertSame(5, $previewRdvs->paginate(15, ['*'], 'page', 2)->count());
    }

    public function test_seeder_is_idempotent_for_direction_and_wilaya_mapping(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(9, Dr::count());
        $this->assertSame(48, Wilaya::count());
        $this->assertDatabaseMissing('drs', ['nom' => 'Tlemcen']);
    }

    public function test_referenced_legacy_tlemcen_direction_is_not_deleted(): void
    {
        $legacyTlemcen = Dr::create(['nom' => 'Tlemcen']);

        Souscripteur::create([
            'code' => 'SUB-TLE',
            'nom' => 'Test',
            'prenom' => 'Tlemcen',
            'nin' => '999999999999999999',
            'prop' => 'F3',
            'wil' => 'Tlemcen',
            'dr_id' => $legacyTlemcen->id,
            'password' => Hash::make('password'),
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('drs', [
            'id' => $legacyTlemcen->id,
            'nom' => 'Tlemcen',
        ]);
        $this->assertWilayaDr('13', 'Tlemcen', 'Oran');
    }

    private function assertWilayaDr(string $code, string $wilayaName, string $drName): void
    {
        $wilaya = Wilaya::where('code', $code)->firstOrFail();
        $dr = Dr::where('nom', $drName)->firstOrFail();

        $this->assertSame($wilayaName, $wilaya->nom);
        $this->assertSame($dr->id, $wilaya->dr_id);
    }

    private function officialDirections(): array
    {
        return [
            'Direction Générale AADL',
            'Alger Est',
            'Alger Ouest',
            'Oran',
            'Tiaret',
            'Constantine',
            'Sétif',
            'Annaba',
            'Ouargla',
        ];
    }

    private function officialWilayaMappings(): array
    {
        return [
            '01' => ['Adrar', 'Oran'],
            '02' => ['Chlef', 'Oran'],
            '03' => ['Laghouat', 'Ouargla'],
            '04' => ['Oum El Bouaghi', 'Constantine'],
            '05' => ['Batna', 'Sétif'],
            '06' => ['Béjaïa', 'Alger Est'],
            '07' => ['Biskra', 'Constantine'],
            '08' => ['Béchar', 'Tiaret'],
            '09' => ['Blida', 'Alger Ouest'],
            '10' => ['Bouira', 'Alger Est'],
            '11' => ['Tamanrasset', 'Ouargla'],
            '12' => ['Tébessa', 'Annaba'],
            '13' => ['Tlemcen', 'Oran'],
            '14' => ['Tiaret', 'Tiaret'],
            '15' => ['Tizi Ouzou', 'Alger Est'],
            '16' => ['Alger', null],
            '17' => ['Djelfa', 'Alger Ouest'],
            '18' => ['Jijel', 'Sétif'],
            '19' => ['Sétif', 'Sétif'],
            '20' => ['Saïda', 'Tiaret'],
            '21' => ['Skikda', 'Annaba'],
            '22' => ['Sidi Bel Abbès', 'Oran'],
            '23' => ['Annaba', 'Annaba'],
            '24' => ['Guelma', 'Annaba'],
            '25' => ['Constantine', 'Constantine'],
            '26' => ['Médéa', 'Alger Ouest'],
            '27' => ['Mostaganem', 'Oran'],
            '28' => ["M'Sila", 'Sétif'],
            '29' => ['Mascara', 'Tiaret'],
            '30' => ['Ouargla', 'Ouargla'],
            '31' => ['Oran', 'Oran'],
            '32' => ['El Bayadh', 'Tiaret'],
            '33' => ['Illizi', 'Ouargla'],
            '34' => ['Bordj Bou Arréridj', 'Sétif'],
            '35' => ['Boumerdès', 'Alger Est'],
            '36' => ['El Tarf', 'Annaba'],
            '37' => ['Tindouf', 'Oran'],
            '38' => ['Tissemsilt', 'Tiaret'],
            '39' => ['El Oued', 'Ouargla'],
            '40' => ['Khenchela', 'Constantine'],
            '41' => ['Souk Ahras', 'Annaba'],
            '42' => ['Tipaza', 'Alger Ouest'],
            '43' => ['Mila', 'Constantine'],
            '44' => ['Aïn Defla', 'Alger Ouest'],
            '45' => ['Naâma', 'Tiaret'],
            '46' => ['Aïn Témouchent', 'Oran'],
            '47' => ['Ghardaïa', 'Ouargla'],
            '48' => ['Relizane', 'Oran'],
        ];
    }
}
