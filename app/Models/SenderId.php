<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SenderId extends Model
{
    protected $table = 'sender_ids';

    protected $fillable = [
        'sender',
        'descricao',
        'tipo',
        'estado',
        'analisado_por',
        'analisado_em',
        'operadora',
        'aprovado_operadora_em',
        'motivo_rejeicao',
        'observacao',
        'aprovado_em',
        'suspenso_em',
    ];

    protected $casts = [
        'analisado_em' => 'datetime',
        'aprovado_operadora_em' => 'datetime',
        'aprovado_em' => 'datetime',
        'suspenso_em' => 'datetime',
    ];

    public function contas(): BelongsToMany
    {
        return $this->belongsToMany(Conta::class, 'conta_sender_ids','sender_id','conta_id')->withPivot(['estado','atribuido_por','atribuido_em'])->withTimestamps();
    }

    public function analisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class,'analisado_por');
    }

    public function estaAprovado(): bool
    {
        return $this->estado === 'APROVADO';
    }

    public function compartilhado(): bool
    {
        return $this->tipo === 'COMPARTILHADO';
    }

}