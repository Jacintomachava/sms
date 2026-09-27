<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contacto extends Model
{
    use SoftDeletes;

    protected $table = 'contactos';

    protected $fillable = [
        'conta_id',
        'nome',
        'telefone',
        'email',
        'data_nascimento',
        'estado',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(
            GrupoContacto::class,
            'contacto_grupo',
            'contacto_id',
            'grupo_contacto_id'
        )->withTimestamps();
    }
}