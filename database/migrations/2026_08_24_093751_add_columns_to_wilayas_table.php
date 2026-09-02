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
        Schema::table('wilayas', function (Blueprint $table) {
            $table->unsignedBigInteger('dr_id')->nullable()->after('nom');
            $table->foreign('dr_id')->references('id')->on('drs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wilayas', function (Blueprint $table) {
            $table->dropForeign(['dr_id']);
            $table->dropColumn('dr_id');
        });
    }
};
