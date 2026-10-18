<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsOutbox extends Model
{
    protected $table = 'sms_outbox';

    protected $fillable = [
        'sms_id',
        'estado',
        'tentativas_publicacao',
        'publicado_em',
        'processado_em',
    ];

    protected $casts = [
        'tentativas_publicacao' => 'integer',
        'publicado_em' => 'datetime',
        'processado_em' => 'datetime',
    ];

    public function sms(): BelongsTo
    {
        return $this->belongsTo(Sms::class);
    }
}
