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
        Schema::create('conta_limites_internos_sms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
                /*
                * Limite máximo permitido pela INFORDATA.
                */
                $table->unsignedBigInteger('limite_sms');
                $table->enum('periodo', ['MENSAL'])->default('MENSAL');
                /*
                * Quando activo, este limite é absoluto.
                */
                $table->boolean('activo')->default(true);
                /*
                * Auditoria.
                */
                $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('alterado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique([
                    'conta_id',
                    'periodo'
                ]);
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conta_limites_internos_sms');
    }
};
