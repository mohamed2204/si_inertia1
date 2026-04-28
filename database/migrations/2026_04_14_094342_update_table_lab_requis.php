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
            $table->integer('nombre_requis')->default(1); // Le quota (ex: 2 pour "Responsables")
            $table->enum('section', ['responsables', 'jours', 'remplacants']); // Pour le tri par bloc
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
