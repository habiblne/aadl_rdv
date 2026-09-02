<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Agent;
use App\Models\Dr;
use App\Models\Responsable;
use App\Models\Souscripteur;
use App\Models\Wilaya;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $directionNames = [
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

        foreach ($directionNames as $directionName) {
            Dr::updateOrCreate(
                ['nom' => $directionName],
                ['nom' => $directionName]
            );
        }

        $this->deleteUnreferencedLegacyTlemcenDirection();

        $directions = Dr::whereIn('nom', $directionNames)->get()->keyBy('nom');
        $generalDr = $directions['Direction Générale AADL'];
        $primaryDr = $directions['Alger Est'];

        $wilayaMappings = [
            ['code' => '01', 'nom' => 'Adrar', 'dr' => 'Oran'],
            ['code' => '02', 'nom' => 'Chlef', 'dr' => 'Oran'],
            ['code' => '03', 'nom' => 'Laghouat', 'dr' => 'Ouargla'],
            ['code' => '04', 'nom' => 'Oum El Bouaghi', 'dr' => 'Constantine'],
            ['code' => '05', 'nom' => 'Batna', 'dr' => 'Sétif'],
            ['code' => '06', 'nom' => 'Béjaïa', 'dr' => 'Alger Est'],
            ['code' => '07', 'nom' => 'Biskra', 'dr' => 'Constantine'],
            ['code' => '08', 'nom' => 'Béchar', 'dr' => 'Tiaret'],
            ['code' => '09', 'nom' => 'Blida', 'dr' => 'Alger Ouest'],
            ['code' => '10', 'nom' => 'Bouira', 'dr' => 'Alger Est'],
            ['code' => '11', 'nom' => 'Tamanrasset', 'dr' => 'Ouargla'],
            ['code' => '12', 'nom' => 'Tébessa', 'dr' => 'Annaba'],
            ['code' => '13', 'nom' => 'Tlemcen', 'dr' => 'Oran'],
            ['code' => '14', 'nom' => 'Tiaret', 'dr' => 'Tiaret'],
            ['code' => '15', 'nom' => 'Tizi Ouzou', 'dr' => 'Alger Est'],
            ['code' => '16', 'nom' => 'Alger', 'dr' => null],
            ['code' => '17', 'nom' => 'Djelfa', 'dr' => 'Alger Ouest'],
            ['code' => '18', 'nom' => 'Jijel', 'dr' => 'Sétif'],
            ['code' => '19', 'nom' => 'Sétif', 'dr' => 'Sétif'],
            ['code' => '20', 'nom' => 'Saïda', 'dr' => 'Tiaret'],
            ['code' => '21', 'nom' => 'Skikda', 'dr' => 'Annaba'],
            ['code' => '22', 'nom' => 'Sidi Bel Abbès', 'dr' => 'Oran'],
            ['code' => '23', 'nom' => 'Annaba', 'dr' => 'Annaba'],
            ['code' => '24', 'nom' => 'Guelma', 'dr' => 'Annaba'],
            ['code' => '25', 'nom' => 'Constantine', 'dr' => 'Constantine'],
            ['code' => '26', 'nom' => 'Médéa', 'dr' => 'Alger Ouest'],
            ['code' => '27', 'nom' => 'Mostaganem', 'dr' => 'Oran'],
            ['code' => '28', 'nom' => "M'Sila", 'dr' => 'Sétif'],
            ['code' => '29', 'nom' => 'Mascara', 'dr' => 'Tiaret'],
            ['code' => '30', 'nom' => 'Ouargla', 'dr' => 'Ouargla'],
            ['code' => '31', 'nom' => 'Oran', 'dr' => 'Oran'],
            ['code' => '32', 'nom' => 'El Bayadh', 'dr' => 'Tiaret'],
            ['code' => '33', 'nom' => 'Illizi', 'dr' => 'Ouargla'],
            ['code' => '34', 'nom' => 'Bordj Bou Arréridj', 'dr' => 'Sétif'],
            ['code' => '35', 'nom' => 'Boumerdès', 'dr' => 'Alger Est'],
            ['code' => '36', 'nom' => 'El Tarf', 'dr' => 'Annaba'],
            ['code' => '37', 'nom' => 'Tindouf', 'dr' => 'Oran'],
            ['code' => '38', 'nom' => 'Tissemsilt', 'dr' => 'Tiaret'],
            ['code' => '39', 'nom' => 'El Oued', 'dr' => 'Ouargla'],
            ['code' => '40', 'nom' => 'Khenchela', 'dr' => 'Constantine'],
            ['code' => '41', 'nom' => 'Souk Ahras', 'dr' => 'Annaba'],
            ['code' => '42', 'nom' => 'Tipaza', 'dr' => 'Alger Ouest'],
            ['code' => '43', 'nom' => 'Mila', 'dr' => 'Constantine'],
            ['code' => '44', 'nom' => 'Aïn Defla', 'dr' => 'Alger Ouest'],
            ['code' => '45', 'nom' => 'Naâma', 'dr' => 'Tiaret'],
            ['code' => '46', 'nom' => 'Aïn Témouchent', 'dr' => 'Oran'],
            ['code' => '47', 'nom' => 'Ghardaïa', 'dr' => 'Ouargla'],
            ['code' => '48', 'nom' => 'Relizane', 'dr' => 'Oran'],
        ];

        foreach ($wilayaMappings as $wilaya) {
            Wilaya::updateOrCreate(
                ['code' => $wilaya['code']],
                [
                    'nom' => $wilaya['nom'],
                    'dr_id' => $wilaya['dr'] ? $directions[$wilaya['dr']]->id : null,
                ]
            );
        }

        $this->deleteUnreferencedLegacyTlemcenDirection();

        // Development-only test accounts for local and pilot demonstrations.
        Souscripteur::updateOrCreate(
            ['code' => 'SUB001'],
            [
                'nom' => 'Test',
                'prenom' => 'Souscripteur',
                'nin' => '111111111111111111',
                'prop' => 'F3',
                'wil' => '16',
                'dr_id' => $primaryDr->id,
                'password' => Hash::make('password'),
            ]
        );

        Responsable::updateOrCreate(
            ['email' => 'responsable@aadl.test'],
            [
                'password' => Hash::make('password'),
                'dr_id' => $primaryDr->id,
            ]
        );

        Agent::updateOrCreate(
            ['email' => 'agent@aadl.test'],
            [
                'password' => Hash::make('password'),
                'dr_id' => $primaryDr->id,
            ]
        );

        Responsable::updateOrCreate(
            ['email' => 'responsable.dg@aadl.test'],
            [
                'password' => Hash::make('password'),
                'dr_id' => $generalDr->id,
            ]
        );

        Agent::updateOrCreate(
            ['email' => 'agent.dg@aadl.test'],
            [
                'password' => Hash::make('password'),
                'dr_id' => $generalDr->id,
            ]
        );

        Admin::updateOrCreate(
            ['email' => 'admin@aadl.test'],
            ['password' => Hash::make('password')]
        );
    }

    private function deleteUnreferencedLegacyTlemcenDirection(): void
    {
        Dr::where('nom', 'Tlemcen')->get()->each(function (Dr $dr): void {
            $isReferenced = $dr->rdvs()->exists()
                || $dr->responsables()->exists()
                || $dr->agents()->exists()
                || $dr->wilayas()->exists()
                || Souscripteur::where('dr_id', $dr->id)->exists();

            if (! $isReferenced) {
                $dr->delete();
            }
        });
    }
}
