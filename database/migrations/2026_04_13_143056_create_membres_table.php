<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('membres', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED (Standard Laravel/MySQL)

            // Relation avec le département parent
            $table->foreignId('departement_id')
                ->constrained('departements')
                ->onDelete('cascade');

            // Informations personnelles
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('matricule', 50)->unique()->nullable();

            // État et paramètres
            $table->boolean('est_actif')->default(true);
            $table->string('email', 150)->unique()->nullable();

            $table->timestamps();

            // Index pour accélérer les recherches par nom (utile pour vos 9 labos)
            $table->index(['nom', 'prenom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membres');
    }
};
