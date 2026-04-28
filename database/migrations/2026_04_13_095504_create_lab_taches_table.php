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
        Schema::create('lab_taches', function (Blueprint $table) {
            $table->id();
            // Le lien vers le labo (utilisé dans le WHERE de votre erreur)
            $table->foreignId('lab_id')->constrained('sous_departements')->onDelete('cascade');

            // La colonne manquante selon l'erreur SQL
            $table->string('tache_id'); // Ou foreignId si vous avez une table 'taches' séparée

            // Optionnel : un libelle plus clair pour l'affichage
            $table->string('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_taches');
    }
};
