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
        Schema::create('designations', function (Blueprint $table) {
            $table->id();

            // 1. Rattachement (Niveau 3)
            $table->foreignId('laboratoire_id')
                ->constrained('laboratoires')
                ->onDelete('cascade');

            // 2. Temporel
            $table->date('date_debut'); // ex: Le lundi de la semaine
            $table->date('date_fin');   // ex: Le dimanche de la semaine
            $table->string('semaine_nom')->nullable(); // ex: "Semaine 16 - 2026"

            // 3. État du flux de travail (Workflow)
            $table->string('statut')->default('brouillon'); // brouillon, validé, publié

            // 4. Métadonnées
            $table->text('notes_generales')->nullable();
            $table->foreignId('createur_id')->constrained('users'); // Qui a créé la désignation

            $table->timestamps();

            // Index pour chercher rapidement par période
            $table->index(['date_debut', 'date_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designation');
    }
};
