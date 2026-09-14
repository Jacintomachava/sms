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
        // permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nome');
            $table->string('modulo');
            $table->enum('scope', ['ACCOUNT','PLATFORM']);
            $table->string('descricao')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['scope', 'modulo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
