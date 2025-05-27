<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckReception extends Model
{
    use HasFactory;

    protected $fillable = [
        'reception_id',
        'essuie_glace',
        'vitres_avant',
        'vitres_arriere',
        'phares_avant',
        'phares_arriere',
        'pneus_secours',
        'cric',
        'peinture',
        'retroviseur',
        'kit_pharmacie',
        'triangle',
        'remarques'
    ];

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }
}
