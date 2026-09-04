<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Log;

class LogController extends Controller
{
    public function index(Request $request)
    {
        // 1. Initialise la requête avec la relation utilisateur
        $query = Log::with('user');

        // 2. Applique le filtre "action" s'il est présent et non vide dans la requête HTTP
        if ($request->has('action') && !empty($request->input('action'))) {
            $query->where('action', $request->input('action'));
        }

        // 3. Récupère les résultats paginés
        $logs = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'logs' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

}
