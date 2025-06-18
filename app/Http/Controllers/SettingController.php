<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function updateTarifHoraire(Request $request)
    {
        $request->validate([
            'tarif_horaire' => 'required|integer|min:0',
        ]);

        Setting::set('tarif_horaire', $request->tarif_horaire);

        return response()->json([
            'message' => 'Tarif horaire mis à jour',
            'tarif_horaire' => $request->tarif_horaire,
        ]);
    }
}
