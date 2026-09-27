<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Carteira extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'carteiras';

    protected $fillable = [
        'conta_id',
        'saldo_sms',
        'estado',
        'alerta_saldo_baixo_activo',
        'alerta_saldo_baixo',
    ];

    protected $casts = [
        'saldo_sms' => 'integer',
        'alerta_saldo_baixo_activo' => 'boolean',
        'alerta_saldo_baixo' => 'integer',
    ];

    public function conta()
    {
        return $this->belongsTo(Conta::class);
    }

    public function movimentos()
    {
        return $this->hasMany(
            CarteiraMovimento::class,
            'carteira_id'
        );
    }
}