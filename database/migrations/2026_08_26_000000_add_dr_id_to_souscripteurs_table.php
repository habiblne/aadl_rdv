<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('souscripteurs', function (Blueprint $table) {
            $table->foreignId('dr_id')
                ->nullable()
                ->after('wil')
                ->constrained('drs')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('souscripteurs', function (Blueprint $table) {
            $table->dropForeign(['dr_id']);
            $table->dropColumn('dr_id');
        });
    }
};
