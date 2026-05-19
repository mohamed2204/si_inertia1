<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_sous_departement', function (Blueprint $table) {
            $table->id();
            
            // Clés étrangères
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('sous_departement_id')->constrained()->onDelete('cascade');
            
            // Colonne pivot cruciale pour le niveau de droit
            $table->string('niveau_acces'); // ex: 'lecture', 'modification'
            
            $table->timestamps();

            // Index pour accélérer les vérifications de droits
            $table->unique(['group_id', 'sous_departement_id'], 'group_sous_dept_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_sous_departement');
    }
};