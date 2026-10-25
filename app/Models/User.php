<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'pseudo',
        'password',
        'role',
        'photo'
    ];

    protected $hidden = ['password'];

    public function mecanicien()
    {
        return $this->hasOne(Mecanicien::class);
    }

    /**
     * Canal personnalisé pour la diffusion des notifications de l'utilisateur.
     * Permet d'écouter 'user.{id}' côté Laravel Echo au lieu de 'App.Models.User.{id}'.
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'user.' . $this->id;
    }
}
