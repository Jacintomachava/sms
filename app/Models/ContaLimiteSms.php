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
        'limite_sms',
        'alertas_activos',
        'percentuais_alerta',
        'bloqueio_activo',
        'corte_percentual',
        'nome_notificacao',
        'email_notificacao',
        'telefone_notificacao',
        'notificar_email',
        'notificar_sms',
        'activo',
    ];

    protected $casts = [
        'limite_sms' => 'integer',
        'alertas_activos' => 'boolean',
        'percentuais_alerta' => 'array',
        'bloqueio_activo' => 'boolean',
        'corte_percentual' => 'integer',
        'notificar_email' => 'boolean',
        'notificar_sms' => 'boolean',
        'activo' => 'boolean',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }
}