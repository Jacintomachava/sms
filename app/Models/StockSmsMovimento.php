<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockSmsMovimento extends Model
{
    protected $table = 'stock_sms_movimentos';

    protected $fillable = [
        'compra_stock_sms_id',
        'tipo',
        'quantidade_sms',
        'saldo_anterior',
        'saldo_posterior',
        'referencia',
        'descricao',
        'metadata',
        'user_id',
    ];

    protected $casts = [
        'quantidade_sms' => 'integer',
        'saldo_anterior' => 'integer',
        'saldo_posterior' => 'integer',
        'metadata' => 'array',
    ];

    public function lote()
    {
        return $this->belongsTo(CompraStockSms::class,'compra_stock_sms_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}