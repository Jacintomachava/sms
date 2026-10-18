
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'smpp_delivery_reports',
            function (Blueprint $table) {
                $table->id();
                $table->foreignId('sms_id')->nullable()->constrained('sms')->nullOnDelete();
                $table->string('provider_message_id', 150)->nullable();
                $table->string('estado', 30)->nullable();
                $table->string('network_error_code', 50)->nullable();
                $table->string('host', 45)->nullable();
                $table->unsignedInteger('sequence_number')->nullable();
                $table->longText('raw_receipt')->nullable();
                $table->timestamp('recebido_em')->useCurrent();
                $table->timestamp('processado_em')->nullable();
                $table->timestamps();

                $table->index([
                    'provider_message_id',
                    'recebido_em'
                ]);

                $table->index([
                    'sms_id',
                    'recebido_em'
                ]);

                $table->index(['processado_em','id']);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('smpp_delivery_reports');
    }
};
