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
        Schema::create('designation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designation_id')->constrained()->onDelete('cascade');
            $table->foreignId('laboratoire_id')->constrained();
            $table->foreignId('config_jour_id')->constrained();
            $table->foreignId('membre_id')->constrained();
            $table->date('date_effective');

            // Le sélecteur de rôle
            // Valeurs : 'responsable', 'remplacant', 'quotidienne'
            $table->string('type_affectation');

            // Clés étrangères vers les configurations (optionnelles selon le type)
            $table->foreignId('config_responsable_id')->nullable()->constrained();
            $table->foreignId('config_remplacant_id')->nullable()->constrained();
            $table->foreignId('tache_id')->nullable()->constrained('taches');

            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('designations_items', function (Blueprint $table) {
            //
        });
    }
};
