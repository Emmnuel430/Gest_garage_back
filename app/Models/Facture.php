<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'reception_id',
        'montant',
        'date_generation',
        'statut',
        'recu',
        'date_paiement',
        'caissier_id'
    ];

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }

    public function caissier()
    {
        return $this->belongsTo(User::class, 'caissier_id');
    }
}
