<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mecanicien extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom',
        'prenom',
        'type',
        'vehicules_maitrises',
        'experience',
        'contact',
        'contact_urgence',
        'fiche_enrolement'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function vehicules()
    {
        return $this->hasMany(Vehicule::class);
    }
}
