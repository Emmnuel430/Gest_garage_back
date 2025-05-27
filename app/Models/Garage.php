<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'adresse'];

    public function mecaniciens()
    {
        return $this->hasMany(Mecanicien::class);
    }
}
