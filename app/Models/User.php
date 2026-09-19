<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'telefone',
        'password',
        'estado',
        'ultimo_login_em',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'ultimo_login_em' => 'datetime',
        'password' => 'hashed',
    ];

    public function contas()
    {
        return $this->belongsToMany(Conta::class, 'conta_user')
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
