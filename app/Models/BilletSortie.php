<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BilletSortie extends Model
{
    use HasFactory;

    protected $table = 'billets_sortie';

    protected $fillable = [
        'reception_id',
        'user_id',
        'date_generation',
        'fiche_sortie_vehicule'
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
    public function chefAtelier()
    {
        return $this->user();
    }
}
