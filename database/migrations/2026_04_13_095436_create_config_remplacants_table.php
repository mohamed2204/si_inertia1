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
        Schema::create('config_remplacants', function (Blueprint $table) {
            $table->id();
            // Clé étrangère vers le labo
            $table->foreignId('sous_departement_id')
                ->constrained('sous_departements')
                ->onDelete('cascade');

            $table->string('libelle'); // Ex: "Remplaçant n°1"
            $table->integer('ordre')->default(0); // Pour l'affichage dans l'accordéon
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_remplacants');
    }
};
