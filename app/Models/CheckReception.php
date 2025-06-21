<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckReception extends Model
{
    use HasFactory;

    protected $fillable = [
        'reception_id',
        'remarques'
    ];

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }

    public function items()
    {
        return $this->hasMany(CheckReceptionItem::class, 'check_reception_id');
    }

}
