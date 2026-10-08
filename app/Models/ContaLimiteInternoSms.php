<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaLimiteInternoSms extends Model
{
    use HasFactory;

    protected $table = 'conta_limites_internos_sms';

    protected $fillable = [
        'conta_id',
        'limite_sms',
        'periodo',
        'activo',
        'criado_por',
        'alterado_por',
    ];

    protected $casts = [
        'limite_sms' => 'integer',
        'activo' => 'boolean',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class,'criado_por');
    }

    public function alteradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alterado_por');
    }
}