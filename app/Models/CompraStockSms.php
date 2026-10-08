<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraStockSms extends Model
{
    protected $table = 'compras_stock_sms';

    protected $fillable = [
        'operadora',
        'quantidade_sms',
        'quantidade_disponivel',
        'preco_unitario',
        'valor_total',
        'moeda',
        'iva_incluido',
        'referencia',
        'data_compra',
        'documento',
        'observacao',
        'estado',
        'criado_por',
    ];

    protected $casts = [
        'quantidade_sms' => 'integer',
        'quantidade_disponivel' => 'integer',
        'preco_unitario' => 'decimal:4',
        'valor_total' => 'decimal:2',
        'iva_incluido' => 'boolean',
        'data_compra' => 'date',
    ];

    public function criadoPor()
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function movimentos()
    {
        return $this->hasMany(StockSmsMovimento::class,'compra_stock_sms_id');
    }

    public function consumosSms()
    {
        return $this->hasMany(SmsConsumoStock::class, 'compra_stock_sms_id');
    }
}