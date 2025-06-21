<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Facture;
use App\Models\User;
use App\Models\Log;
use App\Models\Reception;
use App\Models\Chrono;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FactureController extends Controller
{
    public function listeFacture()
    {
        $facture = Facture::with([
            'reception.vehicule.mecanicien',
            'reception.reparation',
            'reception.chrono',
            'caissier',
            'reception.billetSortie.chefAtelier',
        ])->get();
        return response()->json($facture);
    }


    public function genererFactureEtArreterChrono(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $reception = Reception::findOrFail($id);
            $facture = Facture::where('reception_id', $id)->firstOrFail();
            $chrono = Chrono::where('reception_id', $id)->firstOrFail();
            $vehicule = $reception->vehicule;
            $chefAtelier = User::findOrFail($reception->chef_atelier_id);

            // 1. Arrêt du chrono
            if ($chrono->end_time) {
                return response()->json(['message' => 'Chrono déjà arrêté.'], 400);
            }

            $chrono->end_time = now();

            // Si le chrono était en pause → on met à jour la pause finale
            if ($chrono->pause_time) {
                $tempsPause = Carbon::parse($chrono->pause_time)->diffInMinutes(now());
                $chrono->temps_total_pause += $tempsPause;

                // Remise à zéro
                $chrono->pause_time = null;
                $chrono->resume_time = null;
            }

            $chrono->save();

            // Calcul de la durée nette
            $start = Carbon::parse($chrono->start_time);
            $end = Carbon::parse($chrono->end_time);
            $durationBrut = $start->diffInMinutes($end);
            $durationNet = $durationBrut - $chrono->temps_total_pause;

            // Enregistrer la durée nette
            $chrono->update(['duree_total' => $durationNet]);

            // 2. Calcul du montant
            $tarifHoraire = (int) Setting::get('tarif_horaire', 2000);
            $nbHeures = ceil($durationNet / 60);
            $montant = $nbHeures * $tarifHoraire;

            // 3. Mise à jour de la facture
            $facture->update([
                'montant' => $montant,
                'date_generation' => now(),
                'statut' => 'generee',
            ]);

            // 4. Générer le PDF du reçu
            $pdfRecu = PDF::loadView('pdf.recu_caisse', [
                'reception' => $reception,
                'chefAtelier' => $chefAtelier,
                'montantHoraire' => $tarifHoraire,
                'montantTotal' => $montant,
                'nbHeures' => $nbHeures,
            ]);

            $recuName = 'recu_caisse_' . $reception->id . '.pdf';
            $recuPath = 'recus/' . $recuName;

            Storage::put('public/' . $recuPath, $pdfRecu->output());

            $facture->update(['recu' => $recuPath]);

            // 5. Log de l'action
            $user = User::findOrFail($request->input('user_id'));

            Log::create([
                'idUser' => $user->id,
                'user_nom' => $user->last_name,
                'user_prenom' => $user->first_name,
                'user_pseudo' => $user->pseudo,
                'user_role' => $user->role,
                'user_doc' => $user->created_at,
                'action' => 'update',
                'table_concernee' => 'factures',
                'details' => "Facture générée (montant : {$montant} FCFA) pour le véhicule {$vehicule->immatriculation} (Réception ID : {$reception->id}), chrono arrêté.",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Facture générée, chrono arrêté !',
                'facture' => $facture,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
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
