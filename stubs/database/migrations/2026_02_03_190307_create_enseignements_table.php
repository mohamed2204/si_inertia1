<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enseignements', function (Blueprint $table) {
            $table->id();

            // Le Professeur qui enseigne
            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->onDelete('cascade');

            // La Promotion concernée (ex: Promotion 2024)
            $table->foreignId('promotion_id')
                ->constrained('promotions')
                ->onDelete('cascade');

            // Le cours spécifique (Matière + Phase + Spécialité)
            $table->foreignId('programme_matiere_id')
                ->constrained('programme_matieres')
                ->onDelete('cascade');

            // Optionnel : Année universitaire ou semestre si nécessaire
            $table->string('annee_scolaire')->nullable();

            // Index pour accélérer les recherches par prof ou par promo
            $table->index(['teacher_id', 'promotion_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enseignements');
    }
};
