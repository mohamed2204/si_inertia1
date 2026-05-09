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
            $table->foreignId('laboratoire_id')->constrained()->onDelete('cascade');
            $table->string('jour'); // <-- Vérifiez que cette ligne est présente
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
