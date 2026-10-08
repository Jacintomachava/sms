<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturaSms extends Model
{
    protected $table = 'facturas_sms';

    protected $fillable = [
        'conta_id',
        'ciclo_consumo_sms_id',
        'numero',
        'periodo_inicio',
        'periodo_fim',
        'quantidade_sms',
        'segmentos',
        'preco_unitario',
        'subtotal',
        'valor_total',
        'moeda',
        'data_emissao',
        'data_vencimento',
        'estado',
        'paga_em',
        'cancelada_em',
        'criado_por',
        'cancelado_por',
        'observacao',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fim' => 'date',

        'quantidade_sms' => 'integer',
        'segmentos' => 'integer',

        'preco_unitario' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'valor_total' => 'decimal:2',

        'data_emissao' => 'date',
        'data_vencimento' => 'date',

        'paga_em' => 'datetime',
        'cancelada_em' => 'datetime',
    ];

    public function conta()
    {
        return $this->belongsTo(Conta::class);
    }

    public function ciclo()
    {
        return $this->belongsTo(CicloConsumoSms::class, 'ciclo_consumo_sms_id');
    }

    public function criadoPor()
    {
        return $this->belongsTo(User::class,'criado_por');
    }

    public function canceladoPor()
    {
        return $this->belongsTo(User::class,'cancelado_por');
    }
}