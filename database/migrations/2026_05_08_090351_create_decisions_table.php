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
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // ex: DEC-20240510-9123
            $table->string('libelle');             // ex: Validation globale Semaine 22
            $table->date('date_decision');         // Date d'officialisation
            $table->date('date_effet');            // Date à laquelle la décision prend effet
            $table->date('date_expiration')->nullable(); // Date d'expiration (optionnelle)
            $table->text('commentaires')->nullable(); // Commentaires ou justifications
            

            // Optionnel : l'utilisateur (Super-Admin) qui a créé cette fédération
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->timestamps();
        });

        // ATTENTION : Il faut ajouter la colonne decision_id dans la table designations
        Schema::table('designations', function (Blueprint $table) {
            $table->foreignId('decision_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
