<?php

namespace App\Http\Controllers;

use App\Models\BilletSortie;
use Illuminate\Http\Request;

class BilletSortieController extends Controller
{
    public function listeBilletSortie()
    {
        $billetSortie = BilletSortie::with([
            'reception.vehicule.mecanicien',
            'reception.chrono',
            'chefAtelier'
        ])->get();


        return response()->json($billetSortie);
    }
}
