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
        Schema::create('config_jours', function (Blueprint $table) {
            $table->id();
            // Clé étrangère vers le labo
            $table->foreignId('laboratoire_id')
                ->constrained('laboratoires')
                ->onDelete('cascade');

            $table->string('libelle'); // Ex: "Lundi", "Mardi"
            $table->integer('ordre_jour')->default(0); // Pour trier (1 pour Lundi, 2 pour Mardi...)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_jours');
    }
};
