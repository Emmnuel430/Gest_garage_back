<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Log;

class SettingController extends Controller
{
    public function updateTarifHoraire(Request $request)
    {
        $request->validate([
            'tarif_horaire' => 'required|integer|min:0',
        ]);

        Setting::set('tarif_horaire', $request->tarif_horaire);

        $user = $request->user();
        if ($user) {
            Log::create([
                'idUser' => $user->id,
                'user_nom' => $user->last_name,
                'user_prenom' => $user->first_name,
                'user_pseudo' => $user->pseudo,
                'user_role' => $user->role,
                'user_doc' => $user->created_at,
                'action' => 'update',
                'table_concernee' => 'settings',
                'details' => "Tarif horaire mis à jour à {$request->tarif_horaire} FCFA",
            ]);
        }

        return response()->json([
            'message' => 'Tarif horaire mis à jour',
            'tarif_horaire' => $request->tarif_horaire,
        ]);
    }
}

