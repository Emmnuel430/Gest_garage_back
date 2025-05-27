<?php

namespace App\Http\Controllers;

use App\Models\Log;

class LogController extends Controller
{
    public function index()
    {
        // Retourne les logs avec les informations des utilisateurs associés
        $logs = Log::with('user')->latest()->paginate(999999999999);
        return response()->json([
            'status' => 'success',
            'logs' => $logs
        ]);
    }
}
