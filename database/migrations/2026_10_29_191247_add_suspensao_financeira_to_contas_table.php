<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas', function (Blueprint $table) {
            $table->string('motivo_suspensao', 50)->nullable();
            $table->timestamp('suspensa_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contas', function (Blueprint $table) {
            $table->dropColumn([
                'motivo_suspensao',
                'suspensa_em',
            ]);
        });
    }
};