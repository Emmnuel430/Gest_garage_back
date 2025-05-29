<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reception;
use App\Models\Log;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\CheckReception;
use App\Models\Chrono;
use App\Models\Reparation;
use App\Models\BilletSortie;
use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class ReceptionController extends Controller
{
    public function addReception(Request $request)
    {
        $validatedVehicule = $request->validate([
            'immatriculation' => 'required|string|max:255',
            'marque' => 'required|string|max:255',
            'modele' => 'required|string|max:255',
            'client_nom' => 'nullable|string|max:255',
            'client_tel' => 'nullable|string|max:50',
            'mecanicien_id' => 'required|exists:mecaniciens,id',
        ]);

        $validatedReception = $request->validate([
            'gardien_id' => 'required|exists:users,id',
            'motif_visite' => 'required|string|max:255',
        ]);

        // Créer le véhicule (ou retrouver si déjà existant selon l'immatriculation)
        $vehicule = Vehicule::firstOrCreate(
            ['immatriculation' => $validatedVehicule['immatriculation']],
            $validatedVehicule
        );

        // Vérifie si une réception existe déjà pour ce véhicule aujourd’hui
        $today = now()->toDateString();
        $existing = Reception::where('vehicule_id', $vehicule->id)
            ->whereDate('date_arrivee', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'error' => 'Une réception existe déjà pour ce véhicule aujourd’hui.'
            ], 400);
        }

        // Création de la réception
        $reception = Reception::create([
            'vehicule_id' => $vehicule->id,
            'gardien_id' => $validatedReception['gardien_id'],
            'motif_visite' => $validatedReception['motif_visite'],
            'date_arrivee' => now(), // Automatique
            'statut' => 'attente',
        ]);

        // Génération du PDF de fiche d’entrée
        $pdf = Pdf::loadView('pdf.fiche_entree_vehicule', compact('reception'));
        $pdfPath = 'vehicules/fiche_entree_' . $vehicule->id . '.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());

        $vehicule->update(['fiche_entree_vehicule' => $pdfPath]);

        // Log de l'action
        $gardien = User::find($validatedReception['gardien_id']);
        Log::create([
            'idUser' => $gardien->id,
            'user_nom' => $gardien->last_name,
            'user_prenom' => $gardien->first_name,
            'user_pseudo' => $gardien->pseudo,
            'user_role' => $gardien->role,
            'user_doc' => $gardien->created_at,
            'action' => 'add',
            'table_concernee' => 'receptions',
            'details' => "Réception créée pour le véhicule {$vehicule->immatriculation} (Réception ID : {$reception->id})",
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Réception et véhicule enregistrés avec succès.',
            'reception' => $reception,
            'vehicule' => $vehicule,
        ], 201);
    }



    public function getReception($id)
    {
        $reception = Reception::with(['vehicule', 'gardien', 'secretaire'])->find($id);
        if (!$reception) {
            return response()->json(['error' => 'Réception non trouvée'], 404);
        }

        return response()->json([
            'status' => 'success',
            'reception' => $reception,
        ], 200);
    }

    public function listeReception()
    {
        $receptions = Reception::with([
            'vehicule.mecanicien',
            'gardien',
            'secretaire',
            'checkReception',
            'chrono',
            /*             'reparation',
                        'billetSortie',
                        'facture' */
        ])->orderByDesc('created_at')->get();

        return response()->json([
            'status' => 'success',
            'receptions' => $receptions,
        ], 200);
    }

    public function updateReception(Request $req, $id)
    {
        $req->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'gardien_id' => 'required|exists:users,id',
            'date_arrivee' => 'required|date',
            'motif_visite' => 'required|string|max:255',
            'statut' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        $reception = Reception::find($id);
        if (!$reception) {
            return response()->json(['error' => 'Réception non trouvée.'], 404);
        }

        $authUser = User::find($req->user_id);
        $oldData = $reception->toArray();

        $reception->update($req->only([
            'vehicule_id',
            'gardien_id',
            'date_arrivee',
            'motif_visite',
            'statut'
        ]));

        $newData = $reception->toArray();
        $modifications = [];
        foreach ($newData as $key => $value) {
            if (in_array($key, ['created_at', 'updated_at']))
                continue;
            if ($oldData[$key] != $value) {
                $modifications[] = ucfirst($key) . " modifié";
            }
        }

        if (count($modifications)) {
            Log::create([
                'idUser' => $authUser->id,
                'user_nom' => $authUser->last_name,
                'user_prenom' => $authUser->first_name,
                'user_pseudo' => $authUser->pseudo,
                'user_role' => $authUser->role,
                'user_doc' => $authUser->created_at,
                'action' => 'update',
                'table_concernee' => 'receptions',
                'details' => "Modifications sur Réception ID {$id} : " . implode(', ', $modifications),
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Réception mise à jour.',
            'reception' => $reception,
        ], 200);
    }

    public function deleteReception(Request $request, $id)
    {
        try {
            $userId = $request->query('user_id');
            $authUser = User::find($userId);

            if (!$authUser) {
                return response()->json(['status' => 'Utilisateur invalide.'], 400);
            }

            $reception = Reception::find($id);
            if (!$reception) {
                return response()->json(['status' => 'Réception introuvable'], 404);
            }

            $reception->delete();

            Log::create([
                'idUser' => $authUser->id,
                'user_nom' => $authUser->last_name,
                'user_prenom' => $authUser->first_name,
                'user_pseudo' => $authUser->pseudo,
                'user_role' => $authUser->role,
                'user_doc' => $authUser->created_at,
                'action' => 'delete',
                'table_concernee' => 'receptions',
                'details' => "Réception ID {$id} supprimée",
                'created_at' => now(),
            ]);

            return response()->json(['status' => 'deleted'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ------------------
    // ------------------
    // ------------------

    public function validerReception(Request $request, $id)
    {
        $reception = Reception::findOrFail($id);

        DB::beginTransaction();

        try {
            $userId = $request->input('user_id');
            $authUser = User::find($userId);

            $vehicule = $reception->vehicule;

            // 1. Valider la réception
            $reception->update([
                'statut' => 'validee', // à adapter selon ton enum ou valeur
                'secretaire_id' => $authUser->id, // id de l'utilisateur qui valide
            ]);

            // 2. Créer le check_reception
            $check = CheckReception::create([
                'reception_id' => $reception->id,
                'essuie_glace' => $request->input('essuie_glace', false),
                'vitres_avant' => $request->input('vitres_avant', false),
                'vitres_arriere' => $request->input('vitres_arriere', false),
                'phares_avant' => $request->input('phares_avant', false),
                'phares_arriere' => $request->input('phares_arriere', false),
                'pneus_secours' => $request->input('pneus_secours', false),
                'cric' => $request->input('cric', false),
                'peinture' => $request->input('peinture', false),
                'retroviseur' => $request->input('retroviseur', false),
                'kit_pharmacie' => $request->input('kit_pharmacie', false),
                'triangle' => $request->input('triangle', false),
                'remarques' => $request->input('remarques', ''),
            ]);

            // 3. Démarrer le chrono
            Chrono::create([
                'reception_id' => $reception->id,
                'start_time' => now(),
            ]);

            Reparation::create([
                'reception_id' => $reception->id,
                'description' => $reception->motif_visite,
                'statut' => 'en_cours',
            ]);

            // 4. Générer le PDF de la fiche de réception
            // Vérifie si le PDF existe déjà
            // Si oui, supprime-le avant de le régénérer
            if ($reception->fiche_reception_vehicule && Storage::exists('public/' . $reception->fiche_reception_vehicule)) {
                Storage::delete('public/' . $reception->fiche_reception_vehicule);
            }
            $pdf = PDF::loadView('pdf.fiche_reception_vehicule', [
                'reception' => $reception,
                'check' => $check,
            ]);

            $pdfName = 'fiche_reception_vehicule_' . $reception->id . '_' . time() . '.pdf';
            $pdfPath = 'receptions/' . $pdfName;

            Storage::put('public/' . $pdfPath, $pdf->output());

            $reception->update([
                'fiche_reception_vehicule' => $pdfPath,
            ]);

            DB::commit();
            Log::create([
                'idUser' => $authUser->id,
                'user_nom' => $authUser->last_name,
                'user_prenom' => $authUser->first_name,
                'user_pseudo' => $authUser->pseudo,
                'user_role' => $authUser->role,
                'user_doc' => $authUser->created_at,
                'action' => 'update',
                'table_concernee' => 'receptions',
                'details' => "Réception validée pour le véhicule {$vehicule->immatriculation} (Réception ID : {$reception->id})",
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => 'Réception validée, check effectué, chrono démarré et fiche générée.',
                'reception' => $reception,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

    public function validerSortieVehicule(Request $request, $id)
    {

        $reception = Reception::findOrFail($id);

        DB::beginTransaction();

        try {
            $userId = $request->input('user_id');
            $chefAtelier = User::findOrFail($userId);

            // 1. Marquer la réparation comme terminée
            $reception->update([
                'statut' => 'termine'
            ]);

            // Màj le statut de la reparation
            $reparation = Reparation::where('reception_id', $reception->id)->first();
            if ($reparation) {
                $reparation->update([
                    'chef_atelier_id' => $userId,
                    'statut' => 'termine'
                ]);
            }

            // 2. Arrêter le chrono
            $chrono = Chrono::where('reception_id', $reception->id)->first();

            if (!$chrono || $chrono->end_time) {
                return response()->json(['message' => 'Chrono introuvable ou déjà arrêté.'], 404);
            }

            $chrono->end_time = now();
            $chrono->save();

            $start = Carbon::parse($chrono->start_time);
            $end = Carbon::parse($chrono->end_time);
            $duration = $start->diffInMinutes($end);
            $chrono->update(['duree_total' => $duration]);

            // 3. Générer le billet de sortie (avec PDF)
            $billetSortie = BilletSortie::create([
                'reception_id' => $reception->id,
                'chef_atelier_id' => $chefAtelier->id,
                'date_generation' => now(),
            ]);

            // Génération du PDF fiche_sortie_vehicule
            $pdf = PDF::loadView('pdf.fiche_sortie_vehicule', [
                'reception' => $reception,
                'chef' => $chefAtelier,
                'billetSortie' => $billetSortie,
            ]);

            $pdfName = 'fiche_sortie_vehicule_' . $reception->id . '_' . time() . '.pdf';
            $pdfPath = 'billets_sortie/' . $pdfName;
            Storage::put('public/' . $pdfPath, $pdf->output());

            // Met à jour le chemin dans le billet de sortie
            $billetSortie->update([
                'fiche_sortie_vehicule' => $pdfPath,
            ]);

            $tarifJournalier = 15000; // Tarif par jour en FCFA
            $nbJours = ceil($chrono->duree_total / (60 * 24));
            $montant = $nbJours * $tarifJournalier;

            $facture = Facture::create([
                'reception_id' => $reception->id,
                'montant' => $montant,
                'date_generation' => now(),
                'statut' => 'en_attente',
                'recu' => null,
                'caissier_id' => null,
            ]);

            // Générer le PDF du reçu de caisse
            $pdfRecu = PDF::loadView('pdf.recu_caisse', [
                'reception' => $reception,
                'billetSortie' => $billetSortie,
                'montantJournalier' => $tarifJournalier,
                'montantTotal' => $montant,
                'nbJours' => $nbJours,
            ]);

            $recuName = 'recu_caisse_' . $reception->id . '_' . time() . '.pdf';
            $recuPath = 'recus/' . $recuName;

            Storage::put('public/' . $recuPath, $pdfRecu->output());

            $facture->update([
                'recu' => $recuPath,
            ]);


            DB::commit();

            Log::create([
                'idUser' => $chefAtelier->id,
                'user_nom' => $chefAtelier->last_name,
                'user_prenom' => $chefAtelier->first_name,
                'user_pseudo' => $chefAtelier->pseudo,
                'user_role' => $chefAtelier->role,
                'user_doc' => $chefAtelier->created_at,
                'action' => 'update',
                'table_concernee' => 'reparations',
                'details' => "Réparation terminée pour le véhicule {$reception->vehicule->immatriculation} (Réception ID : {$reception->id})",
            ]);

            return response()->json([
                'message' => 'Sortie validée, chrono arrêté, billet et facture générés.',
                'billet_sortie' => $billetSortie,
                'facture' => $facture,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la validation de sortie', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }

}
