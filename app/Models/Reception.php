<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reception extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicule_id',
        'gardien_id',
        'chef_atelier_id',
        'date_arrivee',
        'motif_visite',
        'fiche_reception_vehicule',
        'statut',
        'secretaire_id'
    ];


    protected static function booted()
    {
        static::deleting(function ($reception) {
            $reception->vehicule()->delete();
        });
    }
    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function gardien()
    {
        return $this->belongsTo(User::class, 'gardien_id');
    }

    public function secretaire()
    {
        return $this->belongsTo(User::class, 'secretaire_id');
    }

    public function checkReception()
    {
        return $this->hasOne(CheckReception::class);
    }

    public function chrono()
    {
        return $this->hasOne(Chrono::class);
    }

    public function reparation()
    {
        return $this->hasOne(Reparation::class);
    }

    public function billetSortie()
    {
        return $this->hasOne(BilletSortie::class);
    }

    public function facture()
    {
        return $this->hasOne(Facture::class);
    }
}
