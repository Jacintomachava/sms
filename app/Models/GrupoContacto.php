<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GrupoContacto extends Model
{
    use SoftDeletes;

    protected $table = 'grupos_contactos';

    protected $fillable = [
        'conta_id',
        'nome',
        'descricao',
        'estado',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class);
    }

    public function contactos(): BelongsToMany
    {
        return $this->belongsToMany(
            Contacto::class,
            'contacto_grupo',
            'grupo_contacto_id',
            'contacto_id'
        )->withTimestamps();
    }
}