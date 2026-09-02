<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateDrName = DB::table('drs')
            ->select('nom')
            ->groupBy('nom')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateDrName) {
            throw new RuntimeException('Cannot add unique constraint on drs.nom while duplicate direction names exist.');
        }

        Schema::table('drs', function (Blueprint $table) {
            $table->unique('nom', 'drs_nom_unique');
        });

        Schema::table('rdvs', function (Blueprint $table) {
            $table->dropForeign(['dr_id']);
            $table->foreign('dr_id', 'rdvs_dr_id_foreign')
                ->references('id')
                ->on('drs')
                ->restrictOnDelete();

            $table->index(['dr_id', 'date'], 'rdvs_dr_id_date_index');
            $table->index(['souscripteur_id', 'statut'], 'rdvs_souscripteur_id_statut_index');
            $table->index(['souscripteur_id', 'date'], 'rdvs_souscripteur_id_date_index');
        });

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE rdvs ADD CONSTRAINT rdvs_statut_check CHECK (statut IN (0, 1, 2, 3))');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE rdvs DROP CHECK rdvs_statut_check');
        }

        Schema::table('rdvs', function (Blueprint $table) {
            $table->dropIndex('rdvs_dr_id_date_index');
            $table->dropIndex('rdvs_souscripteur_id_statut_index');
            $table->dropIndex('rdvs_souscripteur_id_date_index');

            $table->dropForeign(['dr_id']);
            $table->foreign('dr_id', 'rdvs_dr_id_foreign')
                ->references('id')
                ->on('drs')
                ->cascadeOnDelete();
        });

        Schema::table('drs', function (Blueprint $table) {
            $table->dropUnique('drs_nom_unique');
        });
    }
};
