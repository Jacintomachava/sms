<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraSms extends Model
{
    protected $table = 'compras_sms';

    protected $fillable = [
        'conta_id',
        'tarifa_sms_id',
        'quantidade_sms',
        'preco_unitario',
        'valor_total',
        'moeda',
        'estado',
        'creditada_em',
        'criado_por',
    ];

    protected $casts = [
        'quantidade_sms' => 'integer',
        'preco_unitario' => 'decimal:4',
        'valor_total' => 'decimal:2',
        'creditada_em' => 'datetime',
    ];

    public function conta()
    {
        return $this->belongsTo(Conta::class);
    }

    public function tarifa()
    {
        return $this->belongsTo(
            TarifaSms::class,
            'tarifa_sms_id'
        );
    }

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class);
    }

    public function transacoes()
    {
        return $this->hasMany(
            TransacaoPagamento::class,
            'compra_sms_id'
        );
    }

    public function pagamento()
    {
        return $this->hasOne(
            Pagamento::class,
            'compra_sms_id'
        );
    }


}