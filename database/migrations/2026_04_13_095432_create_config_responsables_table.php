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
        Schema::create('config_responsables', function (Blueprint $table) {
            $table->id();
            // Assurez-vous que le nom est EXACTEMENT le même que dans le code Filament
            $table->foreignId('sous_departement_id')->constrained('sous_departements')->onDelete('cascade');
            $table->string('libelle');
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_responsables');
    }
};
