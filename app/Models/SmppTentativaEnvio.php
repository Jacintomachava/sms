<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmppTentativaEnvio extends Model
{
    protected $table = 'smpp_tentativas_envio';
    
    protected $fillable = [
        'sms_id',
        'host',
        'porta',
        'worker_id',
        'session_id',
        'sequence_number',
        'estado',
        'provider_message_id',
        'command_status',
        'erro',
        'iniciada_em',
        'respondida_em',
    ];

    protected $casts = [
        'sequence_number' => 'integer',
        'command_status' => 'integer',
        'iniciada_em' => 'datetime',
        'respondida_em' => 'datetime',
    ];

    public function sms(): BelongsTo{

        return $this->belongsTo(Sms::class);
    }
}