<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransacaoPagamento extends Model
{
    use HasFactory;

    protected $table = 'transacoes_pagamento';

    protected $fillable = [
        'conta_id',
        'compra_sms_id',
        'forma_pagamento',
        'provider',
        'valor',
        'moeda',
        'telefone',
        'invoice_id',
        'reference_id',
        'provider_transacao_id',
        'provider_codigo',
        'provider_descricao',
        'estado',
        'request_payload',
        'response_payload',
        'erro_tecnico',
        'processada_em',

        // Para transferência/depósito futuramente
        'comprovativo',
        'referencia_bancaria',
        'confirmada_manualmente_em',
        'confirmada_por',
    ];

    protected $casts = [
        'valor' => 'decimal:2',

        'request_payload' => 'array',
        'response_payload' => 'array',

        'processada_em' => 'datetime',
        'confirmada_manualmente_em' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONAMENTOS
    |--------------------------------------------------------------------------
    */

    public function conta()
    {
        return $this->belongsTo(
            Conta::class,
            'conta_id'
        );
    }

    public function compraSms()
    {
        return $this->belongsTo(
            CompraSms::class,
            'compra_sms_id'
        );
    }

    public function pagamento()
    {
        return $this->hasOne(
            Pagamento::class,
            'transacao_pagamento_id'
        );
    }

    public function confirmadoPor()
    {
        return $this->belongsTo(
            User::class,
            'confirmada_por'
        );
    }
}