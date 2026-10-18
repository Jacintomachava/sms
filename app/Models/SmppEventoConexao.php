<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmppEventoConexao extends Model
{
    protected $table = 'smpp_eventos_conexao';

    protected $fillable = [
        'host',
        'porta',
        'tipo',
        'host_anterior',
        'host_novo',
        'codigo_erro',
        'descricao',
        'worker_id',
        'ocorrido_em',
    ];

    protected $casts = [
        'ocorrido_em' => 'datetime',
    ];
}
