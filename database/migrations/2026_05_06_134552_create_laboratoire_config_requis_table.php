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
        //     protected $fillable = [
        //     'lab_config_id',
        //     'libelle',
        //     'ordre',
        //     'is_obligatoire'
        // ];
        Schema::create('laboratoire_config_requis', function (Blueprint $table) {
            $table->id();
            // Clé étrangère vers la configuration du jour du labo
            $table->foreignId('lab_config_id')->constrained('laboratoire_configs')->onDelete('cascade');
            $table->string('libelle');            // ex: "Responsable 1", "Technicien"
            $table->integer('ordre')->default(0); // Pour l'affichage dans les tabs
            $table->boolean('is_obligatoire')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratoire_config_requis');
    }
};
