<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarteiraLoteSms extends Model
{
    protected $table = 'carteira_lotes_sms';

    protected $fillable = [
        'conta_id',
        'quantidade_sms',
        'quantidade_disponivel',
        'preco_venda_unitario',
        'valor_total',
        'moeda',
        'origem',
        'referencia',
        'estado',
        'observacao',
        'criado_por',
        'compra_sms_id',
        'creditado_em',
    ];

    protected $casts = [
        'quantidade_sms' => 'integer',
        'quantidade_disponivel' => 'integer',
        'preco_venda_unitario' => 'decimal:4',
        'valor_total' => 'decimal:2',
        'creditado_em' => 'datetime',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class,'criado_por');
    }

    public function consumos(): HasMany
    {
        return $this->hasMany(SmsConsumoCredito::class,'carteira_lote_sms_id');
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(CompraSms::class, 'compra_sms_id');
    }
}