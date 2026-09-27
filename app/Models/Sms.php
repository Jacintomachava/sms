<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{
    protected $table = 'sms';

    protected $fillable = [
        'conta_id',
        'sender_id',
        'telefone',
        'mensagem',
        'encoding',
        'caracteres',
        'segmentos',
        'origem',
        'estado',
        'provider',
        'provider_message_id',
        'provider_codigo',
        'provider_descricao',
        'enviado_em',
        'entregue_em',
        'falhado_em',
        'criado_por',
    ];

    protected $casts = [
        'caracteres' => 'integer',
        'segmentos' => 'integer',

        'enviado_em' => 'datetime',
        'entregue_em' => 'datetime',
        'falhado_em' => 'datetime',
    ];

    public function conta()
    {
        return $this->belongsTo(Conta::class,'conta_id');
    }

    public function sender()
    {
        return $this->belongsTo(SenderId::class,'sender_id');
    }

    public function criadoPor()
    {
        return $this->belongsTo(User::class,'criado_por');
    }
}