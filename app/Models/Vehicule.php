<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Vehicule extends Model
{
    use HasFactory;

    protected $fillable = [
        'immatriculation',
        'marque',
        'modele',
        'client_nom',
        'client_tel',
        'fiche_entree_vehicule',
        'mecanicien_id'
    ];

    public function mecanicien()
    {
        return $this->belongsTo(Mecanicien::class);
    }

    public function receptions()
    {
        return $this->hasMany(Reception::class);
    }
}
