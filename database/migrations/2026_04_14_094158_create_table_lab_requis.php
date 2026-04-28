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
        Schema::create('lab_requis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratoire_id')->constrained();
            $table->foreignId('role_tache_id')->constrained();
            $table->integer('ordre')->default(0); // Pour l'affichage dans le formulaire
            $table->boolean('est_obligatoire')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_lab_requis');
    }
};
