<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Facture;
use App\Models\User;
use App\Models\Log;

class FactureController extends Controller
{
    public function listeFacture()
    {
        $facture = Facture::with([
            'reception.vehicule.mecanicien',
            'reception.chrono',
            'caissier',
            'reception.billetSortie.chefAtelier',
        ])->get();




        return response()->json($facture);
    }

    public function validerPaiement(Request $request, $id)
    {
        $userId = $request->input('user_id');
        $authUser = User::find($userId);

        if (!$authUser) {
            return response()->json(['message' => 'Utilisateur introuvable'], status: 404);
        }

        $facture = Facture::find($id);

        if (!$facture || $facture->statut === 'payee') {
            return response()->json(['message' => 'Facture introuvable ou déjà payée.'], 404);
        }

        $facture->statut = 'payee';
        $facture->caissier_id = $authUser->id;
        $facture->date_paiement = now();
        $facture->save();

        $reception = $facture->reception;
        $vehicule = $reception->vehicule ?? null;

        // Création du log
        Log::create([
            'idUser' => $authUser->id,
            'user_nom' => $authUser->last_name,
            'user_prenom' => $authUser->first_name,
            'user_pseudo' => $authUser->pseudo,
            'user_role' => $authUser->role,
            'user_doc' => $authUser->created_at,
            'action' => 'update',
            'table_concernee' => 'factures',
            'details' => "Paiement validé pour la facture ID {$facture->id} (Montant : {$facture->montant} FCFA) " . ($vehicule ? " - Véhicule : {$vehicule->immatriculation}" : ''),
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Paiement validé avec succès.'
        ]);
    }

}
