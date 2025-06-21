<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckReceptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_reception_id',
        'check_item_id',
        "valeur", // ex: bon, mauvais, présent, absent
    ];
    public function checkReception()
    {
        return $this->belongsTo(CheckReception::class);
    }
    public function item()
    {
        return $this->belongsTo(CheckItem::class, 'check_item_id');
    }


}
