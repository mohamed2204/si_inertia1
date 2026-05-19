<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions_groupes2', function (Blueprint $table) {
            $table->id();
            
            // Clé étrangère vers les groupes
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            
            // L'action autorisée
            $table->string('type_action'); // ex: 'lecture', 'modification', 'suppression'
            
            // Liaison polymorphe dynamique (Crée module_type et module_id + l'index composite)
            // On utilise nullableMorphs au cas où une permission s'applique à TOUT un module globalement
            $table->nullableMorphs('module'); 
            
            $table->timestamps();

            // Index pour optimiser les requêtes du style : "le groupe X a-t-il l'action Y sur le module Z ?"
            $table->index(['group_id', 'type_action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions_groupes2');
    }
};