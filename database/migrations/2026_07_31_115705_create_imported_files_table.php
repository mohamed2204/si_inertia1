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
        Schema::create('imported_files', function (Blueprint $table) {
            $table->id();
            $table->string('sender_email');               // Adresse e-mail de l'expéditeur
            $table->string('original_filename');          // Nom d'origine du fichier Excel
            $table->string('stored_path')->nullable();    // Chemin relatif où le fichier final est stocké
            $table->dateTime('excel_start_date');         // Date de début lue dans la cellule Excel
            $table->dateTime('expiration_date');          // Date/Heure d'expiration de la procédure
            $table->string('status');                     // Statut ('processed', 'expired', 'failed')
            $table->text('error_message')->nullable();    // Message d'erreur si l'import ou la lecture a échoué
            $table->timestamps();                         // Champs created_at et updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imported_files');
    }
};