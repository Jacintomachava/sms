<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsConsumoStock extends Model
{
    protected $table = 'sms_consumos_stock';

    protected $fillable = [
        'sms_id',
        'compra_stock_sms_id',
        'segmentos',
        'preco_compra_unitario',
        'custo_total',
    ];

    protected $casts = [
        'segmentos' => 'integer',
        'preco_compra_unitario' => 'decimal:4',
        'custo_total' => 'decimal:4',
    ];

    public function sms()
    {
        return $this->belongsTo(Sms::class);
    }

    public function lote()
    {
        return $this->belongsTo(CompraStockSms::class,'compra_stock_sms_id');
    }
}