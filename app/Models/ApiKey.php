<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $table = 'api_keys';

    protected $fillable = [
        'conta_id',
        'nome',
        'key_hash',
        'prefixo',
        'estado',
        'ultimo_uso_em',
        'ultimo_ip',
        'criado_por',
        'revogada_em',
        'revogada_por',
    ];

    /*protected $hidden = [
        'key_hash',
    ];*/

    protected $casts = [
        'ultimo_uso_em' => 'datetime',
        'revogada_em' => 'datetime',
    ];

    public function conta()
    {
        return $this->belongsTo(Conta::class,'conta_id');
    }

    public function criadoPor()
    {
        return $this->belongsTo(User::class,'criado_por');
    }
}