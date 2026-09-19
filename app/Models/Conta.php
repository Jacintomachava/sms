<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conta extends Model
{
    use HasFactory;

    protected $table = 'contas';

    protected $fillable = [
        'nome',
        'tipo',
        'nome_legal',
        'nuit',
        'email',
        'telefone',
        'tipo_cobranca',
        'estado',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'conta_user')
            ->withPivot([
                'id',
                'role_id',
                'estado',
                'suspenso_em',
                'suspenso_por',
                'removido_em',
                'removido_por',
            ])
            ->withTimestamps();
    }
}
