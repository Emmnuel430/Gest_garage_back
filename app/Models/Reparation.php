<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reparation extends Model
{
    use HasFactory;

    protected $fillable = ['reception_id', 'chef_atelier_id', 'description', 'statut'];

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }

    public function chefAtelier()
    {
        return $this->belongsTo(User::class, 'chef_atelier_id');
    }
}
