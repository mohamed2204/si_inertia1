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
        Schema::table('group_sous_departement', function (Blueprint $table) {
            // Utilisation de string au lieu de enum, limité à 30 caractères (largement suffisant)
            $table->string('niveau_acces', 30)->default('lecture');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_sous_departement', function (Blueprint $table) {
            $table->dropColumn('niveau_acces');
        });
    }
};
