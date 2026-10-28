<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsReserva extends Model
{
    protected $table = 'sms_reservas';

    protected $fillable = [
        'sms_id',
        'carteira_lote_sms_id',
        'compra_stock_sms_id',
        'ciclo_consumo_sms_id',
        'tipo',
        'quantidade',
        'estado',
    ];

    protected $casts = [
        'quantidade' => 'integer',
    ];

    public function sms(): BelongsTo
    {
        return $this->belongsTo(Sms::class);
    }

    public function loteCliente(): BelongsTo
    {
        return $this->belongsTo(CarteiraLoteSms::class,'carteira_lote_sms_id');
    }

    public function loteStock(): BelongsTo
    {
        return $this->belongsTo(CompraStockSms::class,'compra_stock_sms_id');
    }

    public function cicloConsumo(): BelongsTo
    {
        return $this->belongsTo(CicloConsumoSms::class,'ciclo_consumo_sms_id');
    }
}
