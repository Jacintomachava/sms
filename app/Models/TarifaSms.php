<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifaSms extends Model
{
    protected $table = 'tarifas_sms';

    protected $fillable = [
        'conta_id',
        'nome',
        'quantidade_minima',
        'quantidade_maxima',
        'preco_sms',
        'publica',
        'activo',
        'data_inicio',
        'data_fim',
        'observacao',
    ];

    protected $casts = [
        'quantidade_minima' => 'integer',
        'quantidade_maxima' => 'integer',
        'preco_sms' => 'decimal:4',
        'publica' => 'boolean',
        'activo' => 'boolean',
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }
}