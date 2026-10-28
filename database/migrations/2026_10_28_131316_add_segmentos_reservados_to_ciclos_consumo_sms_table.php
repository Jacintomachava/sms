<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ciclos_consumo_sms', function (Blueprint $table) {
            $table->unsignedBigInteger('segmentos_reservados')->default(0)->after('segmentos');
        });
    }

    public function down(): void
    {
        Schema::table('ciclos_consumo_sms', function (Blueprint $table) {
            $table->dropColumn('segmentos_reservados');
        });
    }
};