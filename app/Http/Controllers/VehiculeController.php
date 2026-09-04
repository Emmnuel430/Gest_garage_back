<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicule;

class VehiculeController extends Controller
{
    public function listeVehicule(Request $request)
    {
        $query = Vehicule::with([
            'mecanicien',
            'receptions.creePar',
            'receptions.validePar',
            'receptions.reparePar',
            'receptions.gardien',
            'receptions.secretaire',
            'receptions.reparation.user',
            'receptions.billetSortie.user',
            'receptions.facture.user',
        ]);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('immatriculation', 'like', "%{$search}%")
                ->orWhere('marque', 'like', "%{$search}%")
                ->orWhere('modele', 'like', "%{$search}%");
        }

        $vehicules = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'vehicules' => $vehicules->items(),
            'pagination' => [
                'current_page' => $vehicules->currentPage(),
                'per_page' => $vehicules->perPage(),
                'total' => $vehicules->total(),
                'last_page' => $vehicules->lastPage(),
            ],
        ]);
    }
}
