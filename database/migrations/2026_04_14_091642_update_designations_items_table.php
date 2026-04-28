<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designation_items', function (Blueprint $table) {
            $table->id();

            // Lien vers la désignation parente (Hebdomadaire)
            $table->foreignId('designation_id')
                ->constrained('designations')
                ->onDelete('cascade');

            // Le laboratoire spécifique (Niveau 3)
            $table->foreignId('laboratoire_id')
                ->constrained('laboratoires');

            // Le jour configuré (ex: Lundi, Mardi...)
            $table->foreignId('config_jour_id')
                ->constrained('config_jours');

            // Le membre affecté
            $table->foreignId('membre_id')
                ->constrained('membres');

            // La date réelle (calculée ou saisie)
            $table->date('date_effective')->nullable();

            // Champs additionnels pour la complétude
            $table->string('tache_id')->nullable(); // Si vous stockez le nom de la tâche ici
            $table->text('observations')->nullable();

            $table->timestamps();

            // Indexation pour booster les performances de recherche
            $table->index(['designation_id', 'laboratoire_id']);
            $table->index('date_effective');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designation_items');
    }
};
