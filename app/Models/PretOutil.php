<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PretOutil extends Model
{
    use HasFactory;

    protected $table = 'pret_outils';

    protected $fillable = [
        'outil_id',
        'reparation_id',
        'mecanicien_id',
        'quantite',
        'quantite_pretee',
        'statut',
        'est_partage',
        'parent_pret_id'
    ];

    public function outil()
    {
        return $this->belongsTo(Outil::class);
    }

    public function reparation()
    {
        return $this->belongsTo(Reparation::class);
    }

    public function mecanicien()
    {
        return $this->belongsTo(Mecanicien::class);
    }

    public function parent()
    {
        return $this->belongsTo(PretOutil::class, 'parent_pret_id');
    }

    public function children()
    {
        return $this->hasMany(PretOutil::class, 'parent_pret_id');
    }
}
