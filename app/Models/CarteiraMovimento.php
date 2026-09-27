<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarteiraMovimento extends Model
{
    use HasFactory;

    protected $table = 'carteira_movimentos';

    protected $fillable = [
        'carteira_id',
        'tipo',
        'origem',
        'quantidade_sms',
        'saldo_anterior',
        'saldo_posterior',
        'referencia',
        'descricao',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'quantidade_sms' => 'integer',
        'saldo_anterior' => 'integer',
        'saldo_posterior' => 'integer',
        'metadata' => 'array',
    ];

    public function carteira()
    {
        return $this->belongsTo(
            Carteira::class,
            'carteira_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}