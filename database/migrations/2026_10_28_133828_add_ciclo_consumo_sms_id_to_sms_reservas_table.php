<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_reservas', function (Blueprint $table) {
            $table->foreignId('ciclo_consumo_sms_id')
                ->nullable()
                ->after('sms_id')
                ->constrained('ciclos_consumo_sms')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sms_reservas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ciclo_consumo_sms_id');
        });
    }
};