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
        Schema::create('contacto_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contacto_id')->constrained('contactos')->cascadeOnDelete();
            $table->foreignId('grupo_contacto_id')->constrained('grupos_contactos')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['contacto_id','grupo_contacto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacto_grupo');
    }
};
