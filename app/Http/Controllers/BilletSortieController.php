<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BilletSortie;
use App\Models\Reception;

use App\Models\Log;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class BilletSortieController extends Controller
{
    public function listeBilletSortie()
    {
        $billetSortie = BilletSortie::with([
            'reception.vehicule.mecanicien',
            'reception.chrono',
            'chefAtelier'
        ])->get();


        return response()->json($billetSortie);
    }

    public function genererBilletSortie(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $reception = Reception::findOrFail($id);
            $vehicule = $reception->vehicule;
            $chefAtelier = User::findOrFail($request->input('user_id'));

            $reception->update([
                'statut' => 'termine', // à adapter selon ton enum ou valeur
            ]);
            // Création du billet
            $billet = BilletSortie::create([
                'reception_id' => $reception->id,
                'chef_atelier_id' => $chefAtelier->id,
                'date_generation' => now(),
            ]);

            $pdf = PDF::loadView('pdf.fiche_sortie_vehicule', [
                'reception' => $reception,
                'chefAtelier' => $chefAtelier,
                'billetSortie' => $billet,
            ]);

            $pdfName = 'fiche_sortie_vehicule_' . $reception->id . '.pdf';
            $pdfPath = 'billets_sortie/' . $pdfName;
            Storage::put('public/' . $pdfPath, contents: $pdf->output());

            $billet->update(['fiche_sortie_vehicule' => $pdfPath]);

            // Log
            Log::create([
                'idUser' => $chefAtelier->id,
                'user_nom' => $chefAtelier->last_name,
                'user_prenom' => $chefAtelier->first_name,
                'user_pseudo' => $chefAtelier->pseudo,
                'user_role' => $chefAtelier->role,
                'user_doc' => $chefAtelier->created_at,
                'action' => 'create',
                'table_concernee' => 'billets sortie',
                'details' => "Billet de sortie généré pour le véhicule {$vehicule->immatriculation} (Réception ID : {$reception->id})",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Billet de sortie généré !',
                'billet_sortie' => $billet,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }
}
