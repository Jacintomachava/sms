<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'tarifa_sms_id',
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

    public function carteira(): HasOne
    {
        return $this->hasOne(Carteira::class);
    }

    public function senderIds(): BelongsToMany
    {
        return $this->belongsToMany(SenderId::class,'conta_sender_ids','conta_id','sender_id')
        ->withPivot(['estado','atribuido_por','atribuido_em'])
        ->withTimestamps();
    }

    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }

    public function gruposContactos(): HasMany
    {
        return $this->hasMany(GrupoContacto::class);
    }

    public function tarifasSms(): HasMany
    {
        return $this->hasMany(TarifaSms::class);
    }

    public function ciclosConsumoSms()
    {
        return $this->hasMany(CicloConsumoSms::class);
    }

    public function tarifaSms()
    {
        return $this->belongsTo(TarifaSms::class,'tarifa_sms_id');
    }

    public function lotesSms()
    {
        return $this->hasMany(CarteiraLoteSms::class,'conta_id');
    }
}
