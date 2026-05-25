<?php

namespace Database\Migrations;


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            // Lien direct avec la table users que vous avez déjà
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('prenom')->unique()->nullable();
            $table->string('matricule')->unique()->nullable();
            $table->string('grade')->nullable(); // Ex: PES, PA, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};

