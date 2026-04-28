<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taches', function (Blueprint $table) {
            $table->id();

            // Informations de base
            $table->string('nom'); // ex: Analyse PCR, Maintenance UPC, Archivage
            $table->string('code')->unique()->nullable(); // ex: PCR_01
            $table->text('description')->nullable();

            // Catégorisation (Optionnel, utile pour le filtrage)
            $table->string('categorie')->nullable(); // ex: Technique, Administratif

            // Statut
            $table->boolean('est_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taches');
    }
};
