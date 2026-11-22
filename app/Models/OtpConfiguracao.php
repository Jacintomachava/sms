<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpConfiguracao extends Model
{
    use HasFactory;

    protected $table = 'otp_configuracoes';

    protected $fillable = [
        'conta_id',
        'sender_id',
        'tamanho_codigo',
        'validade_minutos',
        'max_tentativas',
        'intervalo_reenvio_segundos',
        'activo',
    ];

    protected $casts = [
        'conta_id' => 'integer',
        'sender_id' => 'integer',
        'tamanho_codigo' => 'integer',
        'validade_minutos' => 'integer',
        'max_tentativas' => 'integer',
        'intervalo_reenvio_segundos' => 'integer',
        'activo' => 'boolean',
    ];

    public function conta()
    {
        return $this->belongsTo(Conta::class);
    }

    public function sender()
    {
        return $this->belongsTo(SenderId::class, 'sender_id');
    }
}
