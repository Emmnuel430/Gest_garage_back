<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reception;
use App\Models\Log;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\CheckReception;
use App\Models\CheckItem;
use App\Models\CheckReceptionItem;
use App\Models\Chrono;
use App\Models\Reparation;
use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;



class ReceptionController extends Controller
{
    public function addReception(Request $request)
    {
        $validatedVehicule = $request->validate([
            'immatriculation' => 'required|string|max:255',
            'marque' => 'required|string|max:255',
            'modele' => 'required|string|max:255',
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
            $authUser = User::findOrFail($userId);

            $vehicule = $reception->vehicule;

            // 1. Valider la réception
            $reception->update([
                'statut' => 'validee',
                'secretaire_id' => $authUser->id,
            ]);

            // 2. Créer le check_reception
            $checkReception = CheckReception::create([
                'reception_id' => $reception->id,
                'remarques' => $request->input('remarques', ''),
            ]);

            // 3. Enregistrer les éléments du check dynamiquement
            $items = CheckItem::all();

            foreach ($items as $item) {
                $inputName = Str::slug($item->nom, '_'); // ex: 'vitres_avant'
                $valeur = $request->input($inputName);

                if ($valeur !== null) {
                    CheckReceptionItem::create([
                        'check_reception_id' => $checkReception->id,
                        'check_item_id' => $item->id,
                        'valeur' => $valeur,
                    ]);
                }
            }

            // 4. Démarrer le chrono
            Chrono::create([
                'reception_id' => $reception->id,
                'start_time' => now(),
            ]);

            // 5. Démarrer la réparation
            Reparation::create([
                'reception_id' => $reception->id,
                'description' => $reception->motif_visite,
                'statut' => 'en_cours',
            ]);

            // 6. Générer le PDF
            // Supprimer l'ancien fichier s'il existe
            if ($reception->fiche_reception_vehicule && Storage::exists('public/' . $reception->fiche_reception_vehicule)) {
                Storage::delete('public/' . $reception->fiche_reception_vehicule);
            }

            // Charger les relations nécessaires pour le PDF
            $checkReception->load('items.item');

            $pdf = PDF::loadView('pdf.fiche_reception_vehicule', [
                'reception' => $reception,
                'check' => $checkReception, // C’est bien le bon objet ici
            ]);

            $pdfName = 'fiche_reception_vehicule_' . $reception->id . '.pdf';
            $pdfPath = 'receptions/' . $pdfName;

            Storage::put('public/' . $pdfPath, $pdf->output());

            $reception->update([
                'fiche_reception_vehicule' => $pdfPath,
            ]);

            // 7. Log
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

            DB::commit();

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

    public function terminerReparation(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $reception = Reception::findOrFail($id);
            $userId = $request->input('user_id');
            $chefAtelier = User::findOrFail($userId);
            $vehicule = $reception->vehicule;

            $reception->update([
                'chef_atelier_id' => $chefAtelier->id,
            ]);
            // 1. Terminer la reparation
            $reparation = Reparation::where('reception_id', $reception->id)->firstOrFail();
            $reparation->update([
                'chef_atelier_id' => $userId,
                'statut' => 'termine'
            ]);


            // 2. Création d'une facture "en attente"
            $facture = Facture::create([
                'reception_id' => $reception->id,
                'montant' => 0,
                'date_generation' => null,
                'statut' => 'en_attente',
                'recu' => null,
                'caissier_id' => null,
            ]);

            // Log de l'action
            Log::create([
                'idUser' => $chefAtelier->id,
                'user_nom' => $chefAtelier->last_name,
                'user_prenom' => $chefAtelier->first_name,
                'user_pseudo' => $chefAtelier->pseudo,
                'user_role' => $chefAtelier->role,
                'user_doc' => $chefAtelier->created_at,
                'action' => 'update',
                'table_concernee' => 'reparations',
                'details' => "Réparation terminée pour le véhicule {$vehicule->immatriculation} (Réception ID : {$reception->id}).",
            ]);

            DB::commit();

            return response()->json([
                'message' => "Réparation terminée !",
                'facture' => $facture
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }






}
