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
        Schema::table('rdvs', function (Blueprint $table) {
            $table->foreignId('accepted_by_responsable_id')
                ->nullable()
                ->after('statut')
                ->constrained('responsables')
                ->nullOnDelete();
            $table->timestamp('accepted_at')->nullable()->after('accepted_by_responsable_id');

            $table->foreignId('validated_by_agent_id')
                ->nullable()
                ->after('accepted_at')
                ->constrained('agents')
                ->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('validated_by_agent_id');

            $table->foreignId('completed_by_responsable_id')
                ->nullable()
                ->after('validated_at')
                ->constrained('responsables')
                ->nullOnDelete();
            $table->timestamp('completed_at')->nullable()->after('completed_by_responsable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rdvs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('accepted_by_responsable_id');
            $table->dropColumn('accepted_at');
            $table->dropConstrainedForeignId('validated_by_agent_id');
            $table->dropColumn('validated_at');
            $table->dropConstrainedForeignId('completed_by_responsable_id');
            $table->dropColumn('completed_at');
        });
    }
};
