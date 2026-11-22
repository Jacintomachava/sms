<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCodigo extends Model
{
    protected $table = 'otp_codigos';

    protected $fillable = [
        'conta_id',
        'sms_id',
        'referencia',
        'telefone',
        'codigo_hash',
        'tentativas',
        'max_tentativas',
        'expira_em',
        'verificado_em',
        'estado',
    ];

    protected $hidden = [
        'codigo_hash',
    ];

    protected $casts = [
        'expira_em' => 'datetime',
        'verificado_em' => 'datetime',
        'tentativas' => 'integer',
        'max_tentativas' => 'integer',
    ];
}
