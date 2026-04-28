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
        Schema::create('role_taches', function (Blueprint $table) {
            $table->id();
            $table->string('libelle'); // ex: Responsable 1, Remplaçant 1, Garde Vendredi
            $table->string('categorie'); // ex: responsable, remplacant, quotidien
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_role_taches');
    }
};
