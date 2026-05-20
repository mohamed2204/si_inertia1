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
        Schema::create('sous_departement_user', function (Blueprint $table) {
            $table->id();
            
            // Clés étrangères vers vos tables existantes
            // Remarque : Si vos tables s'appellent "users" et "sous_departements"
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('sous_departement_id')
                  ->constrained('sous_departements')
                  ->cascadeOnDelete();

            // Droits CRUD spécifiques pour l'utilisateur sur ce sous-département
            $table->boolean('can_create')->default(false)->comment('Droit de Création (C)');
            $table->boolean('can_read')->default(true)->comment('Droit de Lecture (R)');
            $table->boolean('can_update')->default(false)->comment('Droit de Modification (U)');
            $table->boolean('can_delete')->default(false)->comment('Droit de Suppression (D)');

            // Timestamps optionnels pour l'historique/traçabilité
            $table->timestamps();

            // Index unique composite : Évite qu'un utilisateur soit assigné 
            // plusieurs fois au même sous-département dans la table
            $table->unique(['user_id', 'sous_departement_id'], 'user_sub_dept_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sous_departement_user');
    }
};
