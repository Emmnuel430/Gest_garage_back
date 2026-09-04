<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chrono extends Model
{
    use HasFactory;

    protected $fillable = ['reception_id', 'statut', 'start_time', 'end_time', 'duree_total'];

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }
}
