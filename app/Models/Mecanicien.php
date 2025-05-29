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

    public function reparations()
    {
        return $this->hasManyThrough(
            Reparation::class,
            Vehicule::class,
            'mecanicien_id', // Clé étrangère dans `vehicules`
            'reception_id',  // Clé étrangère dans `reparations` pointant vers `receptions`
            'id',            // Clé locale dans `mecaniciens`
            'id'             // Clé locale dans `vehicules`
        )->join('receptions', 'receptions.id', '=', 'reparations.reception_id')
            ->select('reparations.*'); // Optionnel, pour éviter les colonnes doublées
    }

}
