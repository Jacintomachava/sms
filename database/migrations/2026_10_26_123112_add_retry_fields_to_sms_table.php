<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms', function (Blueprint $table) {
            $table->unsignedInteger('tentativas_envio')->default(0);
            $table->timestamp('proxima_tentativa_em')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('sms', function (Blueprint $table) {
            $table->dropColumn([
                'tentativas_envio',
                'proxima_tentativa_em',
            ]);
        });
    }
};