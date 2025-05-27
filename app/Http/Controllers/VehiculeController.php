<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicule;

class VehiculeController extends Controller
{
    public function listeVehicule()
    {
        $vehicule = Vehicule::with([
            'mecanicien',
            'receptions.gardien',
            'receptions.secretaire',
            'receptions.reparation',
            'receptions.billetSortie',
            'receptions.facture',
        ])->get();


        return response()->json($vehicule);
    }
}
