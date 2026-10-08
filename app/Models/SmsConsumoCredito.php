<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsConsumoCredito extends Model
{
    protected $table = 'sms_consumos_creditos';

    protected $fillable = [
        'sms_id',
        'carteira_lote_sms_id',
        'segmentos',
        'preco_venda_unitario',
        'valor_total',
    ];

    protected $casts = [
        'segmentos' => 'integer',
        'preco_venda_unitario' => 'decimal:4',
        'valor_total' => 'decimal:4',
    ];

    public function sms(): BelongsTo
    {
        return $this->belongsTo(Sms::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(CarteiraLoteSms::class, 'carteira_lote_sms_id');
    }
}