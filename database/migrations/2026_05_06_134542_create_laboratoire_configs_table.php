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
        Schema::create('laboratoire_configs', function (Blueprint $table) {
            $table->id();
            // Lien vers le laboratoire (Sous-département/Unité)
            $table->foreignId('laboratoire_id')->constrained()->onDelete('cascade');

            // Identifiant du jour (ex: 'lun', 'mar' ou 1, 2...)
            $table->string('jour');

            // Label affiché sur la carte (ex: 'Lundi', 'Monday')
            $table->string('jour_label');

            $table->integer('ordre_affichage')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratoire_configs');
    }
};
