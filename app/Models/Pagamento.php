<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    use HasFactory;

    protected $table = 'pagamentos';

    protected $fillable = [
        'conta_id',
        'compra_sms_id',
        'transacao_pagamento_id',
        'tipo',
        'forma_pagamento',
        'valor',
        'moeda',
        'referencia',
        'pago_em',
        'confirmado_por',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'pago_em' => 'datetime',
    ];

    public function conta()
    {
        return $this->belongsTo(Conta::class);
    }

    public function compraSms()
    {
        return $this->belongsTo(
            CompraSms::class,
            'compra_sms_id'
        );
    }

    public function transacao()
    {
        return $this->belongsTo(
            TransacaoPagamento::class,
            'transacao_pagamento_id'
        );
    }

    public function confirmadoPor()
    {
        return $this->belongsTo(
            User::class,
            'confirmado_por'
        );
    }
}