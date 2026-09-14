<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{

    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'telefone',
        'password',
        'estado',
        'ultimo_login_em',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_login_em' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function contas()
    {
        return $this->belongsToMany(Conta::class,'conta_user','user_id','conta_id')->withPivot(['id','role_id','estado','suspenso_em','suspenso_por','removido_em','removido_por',])->withTimestamps();
    }

    public function contaUsers()
    {
        return $this->hasMany(ContaUser::class);
    }

}
