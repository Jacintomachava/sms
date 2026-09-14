<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContaUser extends Model
{
    use HasFactory;

    protected $table = 'conta_user';

    protected $fillable = [
        'conta_id',
        'user_id',
        'role_id',
        'estado',
        'suspenso_em',
        'suspenso_por',
        'removido_em',
        'removido_por',
    ];

    public function conta()
    {
        return $this->belongsTo(Conta::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
