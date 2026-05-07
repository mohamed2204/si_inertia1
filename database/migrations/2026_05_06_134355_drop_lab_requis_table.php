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
        Schema::dropIfExists('lab_requis');
    }

    public function down(): void
    {
        // Optionnel : recréer la table si vous faites un rollback
        Schema::create('lab_requis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratoire_id');
            $table->foreignId('role_tache_id');
            $table->integer('nombre_requis');
            $table->string('section');
            $table->integer('ordre');
            $table->timestamps();
        });
    }
};
