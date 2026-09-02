<?php

namespace Tests\Feature;

use App\Models\Dr;
use App\Models\Rdv;
use App\Models\Souscripteur;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_dr_names_are_unique_at_database_level(): void
    {
        Dr::create(['nom' => 'Alger Est']);

        $this->expectException(QueryException::class);

        Dr::create(['nom' => 'Alger Est']);
    }

    public function test_deleting_a_dr_does_not_delete_rdv_history(): void
    {
        $dr = Dr::create(['nom' => 'Alger Est']);
        $souscripteur = $this->createSouscripteur($dr);
        $rdv = Rdv::create([
            'souscripteur_id' => $souscripteur->id,
            'dr_id' => $dr->id,
            'date' => '2026-08-10',
            'motif' => 'Depot de dossier',
            'statut' => Rdv::STATUT_RDV_PRIS,
        ]);

        try {
            $dr->delete();
        } catch (QueryException) {
            // Expected: RDV history must keep its existing direction reference.
        }

        $this->assertDatabaseHas('drs', ['id' => $dr->id]);
        $this->assertDatabaseHas('rdvs', ['id' => $rdv->id, 'dr_id' => $dr->id]);
    }

    public function test_rdv_lookup_indexes_exist(): void
    {
        $indexNames = collect(DB::select("PRAGMA index_list('rdvs')"))
            ->pluck('name')
            ->all();

        $this->assertContains('rdvs_dr_id_date_index', $indexNames);
        $this->assertContains('rdvs_souscripteur_id_statut_index', $indexNames);
        $this->assertContains('rdvs_souscripteur_id_date_index', $indexNames);
    }

    private function createSouscripteur(Dr $dr): Souscripteur
    {
        return Souscripteur::create([
            'code' => 'SUB001',
            'nom' => 'Test',
            'prenom' => 'Souscripteur',
            'nin' => '111111111111111111',
            'prop' => 'F3',
            'wil' => 'Alger',
            'dr_id' => $dr->id,
            'password' => Hash::make('password'),
        ]);
    }
}
