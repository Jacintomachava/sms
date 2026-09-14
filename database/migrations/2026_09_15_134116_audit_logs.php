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
        // audit_logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('conta_id')->nullable()->constrained('contas')->nullOnDelete();
            $table->enum('origem', ['PLATFORM','PORTAL','API','SYSTEM','JOB','WEBHOOK']);
            $table->string('acao', 100);
            $table->string('recurso', 100);
            $table->string('recurso_id')->nullable();
            $table->json('antes')->nullable();
            $table->json('depois')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('motivo')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['conta_id','created_at']);
            $table->index(['user_id','created_at']);
            $table->index(['recurso','recurso_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
