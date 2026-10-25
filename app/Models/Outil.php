<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outil extends Model
{
    use HasFactory;

    protected $fillable = ['libelle', 'quantite', 'reference', 'est_special', 'prix_par_heure'];

    protected $casts = [
        'est_special' => 'boolean',
        'prix_par_heure' => 'integer',
        'quantite' => 'integer',
    ];

    public function prets()
    {
        return $this->hasMany(PretOutil::class);
    }
}
