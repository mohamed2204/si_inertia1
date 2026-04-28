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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

// Pivot : Utilisateurs <-> Groupes
        Schema::create('group_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
        });

// Pivot : Groupes <-> Sous-Départements (La clé du changement)
        Schema::create('group_sous_departement', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sous_departement_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Groups', function (Blueprint $table) {
            $table->drop();
        });

        Schema::table('group_user', function (Blueprint $table) {
            $table->drop();
        });

        Schema::table('group_sous_departement', function (Blueprint $table) {
            $table->drop();
        });



    }
};
