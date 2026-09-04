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
        'user_id'
    ];

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Alias rétrocompatible
    public function caissier()
    {
        return $this->user();
    }
}
