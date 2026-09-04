<?php

namespace App\Http\Controllers;

use App\Models\Reparation;
use Illuminate\Http\Request;

class ReparationController extends Controller
{
    public function listeReparations(Request $request)
    {
        $query = Reparation::with([
            'reception.vehicule.mecanicien',
            'reception.billetSortie.user',
            'reception.chrono',
            'user',
        ]);

        if ($request->filled('mecanicien_id')) {
            $mecanicienId = $request->query('mecanicien_id');
            $query->whereHas('reception.vehicule', function ($q) use ($mecanicienId) {
                $q->where('mecanicien_id', $mecanicienId);
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('reception.vehicule', function ($q) use ($search) {
                $q->where('immatriculation', 'like', "%{$search}%")
                    ->orWhere('marque', 'like', "%{$search}%")
                    ->orWhere('modele', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('all') || $request->query('all') === 'true') {
            $reparations = $query->latest()->get();
            return response()->json([
                'status' => 'success',
                'reparations' => $reparations,
            ]);
        }

        $reparations = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'reparations' => $reparations->items(),
            'pagination' => [
                'current_page' => $reparations->currentPage(),
                'per_page' => $reparations->perPage(),
                'total' => $reparations->total(),
                'last_page' => $reparations->lastPage(),
            ],
        ]);
    }
}
