<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CicloConsumoSms extends Model
{
    protected $table = 'ciclos_consumo_sms';

    protected $fillable = [
        'conta_id',
        'tarifa_sms_id',
        'periodo_inicio',
        'periodo_fim',
        'quantidade_sms',
        'segmentos',
        'preco_unitario',
        'valor_estimado',
        'moeda',
        'estado',
        'fechado_em',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fim' => 'date',
        'quantidade_sms' => 'integer',
        'segmentos' => 'integer',
        'preco_unitario' => 'decimal:4',
        'valor_estimado' => 'decimal:2',
        'fechado_em' => 'datetime',
    ];

    public function conta()
    {
        return $this->belongsTo(Conta::class);
    }

    public function tarifa()
    {
        return $this->belongsTo(TarifaSms::class,'tarifa_sms_id');
    }

    public function factura()
    {
        return $this->hasOne(FacturaSms::class,'ciclo_consumo_sms_id');
    }
}