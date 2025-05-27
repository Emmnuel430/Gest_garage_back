<?php

namespace App\Http\Controllers;

use App\Models\Reparation;
use Illuminate\Http\Request;

class ReparationController extends Controller
{
    public function listeReparations()
    {
        $reparations = Reparation::with([
            'reception.vehicule.mecanicien',
            'reception.billetSortie',
            'reception.chrono'
        ])->get();


        return response()->json($reparations);
    }
}
