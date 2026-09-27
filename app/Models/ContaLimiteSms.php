<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaLimiteSms extends Model
{
    protected $table = 'conta_limites_sms';

    protected $fillable = [
        'conta_id',
        'periodo',
        'limite_orcamento',
        'alerta_activo',
        'limite_alerta',
        'bloqueio_activo',
        'limite_bloqueio',
        'notificar_email',
        'notificar_sms',
        'activo',
    ];

    protected $casts = [
        'limite_orcamento' => 'decimal:2',
        'limite_alerta' => 'decimal:2',
        'limite_bloqueio' => 'decimal:2',
        'alerta_activo' => 'boolean',
        'bloqueio_activo' => 'boolean',
        'notificar_email' => 'boolean',
        'notificar_sms' => 'boolean',
        'activo' => 'boolean',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }
}