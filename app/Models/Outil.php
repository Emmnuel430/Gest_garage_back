<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outil extends Model
{
    use HasFactory;

    protected $fillable = ['libelle', 'quantite', 'reference'];

    public function prets()
    {
        return $this->hasMany(PretOutil::class);
    }
}
