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
        Schema::table('designation_items', function (Blueprint $table) {
            // Lien vers le poste de responsable configuré (ex: "Responsable Matin")
            $table->foreignId('config_responsable_id')
                ->nullable()
                ->constrained('config_responsables')
                ->onDelete('set null');

            // Lien vers le poste de remplaçant configuré (ex: "Remplaçant Garde")
            $table->foreignId('config_remplacant_id')
                ->nullable()
                ->constrained('config_remplacants')
                ->onDelete('set null');

            // Type d'affectation pour faciliter les filtres (Optionnel)
            // Valeurs possibles : 'responsable', 'remplacant', 'tache_simple'
            $table->string('type_role')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('_designations_items', function (Blueprint $table) {
            //
        });
    }
};
