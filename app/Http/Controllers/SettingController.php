<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Log;

class SettingController extends Controller
{
    public function getSettings()
    {
        return response()->json([
            'tarif_horaire' => (int) Setting::get('tarif_horaire', 1000),
            'prix_entree' => (int) Setting::get('prix_entree', 5000),
            'tarif_jours_supp' => (int) Setting::get('tarif_jours_supp', 2000),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'tarif_horaire' => 'nullable|integer|min:0',
            'prix_entree' => 'nullable|integer|min:0',
            'tarif_jours_supp' => 'nullable|integer|min:0',
        ]);

        if ($request->has('tarif_horaire')) {
            Setting::set('tarif_horaire', $request->tarif_horaire);
        }

        if ($request->has('prix_entree')) {
            Setting::set('prix_entree', $request->prix_entree);
        }

        if ($request->has('tarif_jours_supp')) {
            Setting::set('tarif_jours_supp', $request->tarif_jours_supp);
        }

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
                'details' => "Mise à jour des tarifs (Horaire: {$request->tarif_horaire}, Entrée: {$request->prix_entree}, Jours supp: {$request->tarif_jours_supp})",
            ]);
        }

        return $this->getSettings();
    }

    public function updateTarifHoraire(Request $request)
    {
        return $this->updateSettings($request);
    }
}


