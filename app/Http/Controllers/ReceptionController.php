<?php

namespace App\Http\Controllers;

use App\Models\PretOutil;
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
        $user = $request->user();

        $validatedVehicule = $request->validate([
            'immatriculation' => 'required|string|max:255',
            'marque' => 'required|string|max:255',
            'modele' => 'required|string|max:255',
            'mecanicien_id' => 'required|exists:mecaniciens,id',
        ]);

        $validatedReception = $request->validate([
            'motif_visite' => 'required|string|max:255',
        ]);

        // Créer le véhicule (ou le retrouver si déjà existant)
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

        // Création de la réception (L'Observer intercepte la création ici et génère le PDF + Log)
        $reception = Reception::create([
            'vehicule_id' => $vehicule->id,
            'created_by_id' => $user->id,
            'motif_visite' => $validatedReception['motif_visite'],
            'date_arrivee' => now(),
            'statut' => 'attente',
        ]);

        // On rafraîchit l'instance du véhicule pour avoir le nouveau chemin du PDF mis à jour par l'Observer
        $vehicule->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Réception et véhicule enregistrés avec succès.',
            'reception' => $reception,
            'vehicule' => $vehicule,
        ], 201);
    }


    public function getReception($id)
    {
        $reception = Reception::with(['vehicule', 'creePar', 'validePar', 'reparePar'])->find($id);
        if (!$reception) {
            return response()->json(['error' => 'Réception non trouvée'], 404);
        }

        return response()->json([
            'status' => 'success',
            'reception' => $reception,
        ], 200);
    }

    public function listeReception(Request $request)
    {
        $query = Reception::with([
            'vehicule.mecanicien',
            'creePar',
            'validePar',
            'reparePar',
            'checkReception',
            'chrono',
        ]);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('vehicule', function ($q) use ($search) {
                $q->where('immatriculation', 'like', "%{$search}%")
                    ->orWhere('marque', 'like', "%{$search}%")
                    ->orWhere('modele', 'like', "%{$search}%");
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        $receptions = $query->orderByDesc('created_at')->paginate(15);

        return response()->json([
            'status' => 'success',
            'receptions' => $receptions->items(),
            'pagination' => [
                'current_page' => $receptions->currentPage(),
                'per_page' => $receptions->perPage(),
                'total' => $receptions->total(),
                'last_page' => $receptions->lastPage(),
            ],
        ], 200);
    }

    public function updateReception(Request $req, $id)
    {
        $user = $req->user();

        $req->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'date_arrivee' => 'required|date',
            'motif_visite' => 'required|string|max:255',
            'statut' => 'nullable|string',
        ]);

        $reception = Reception::find($id);
        if (!$reception) {
            return response()->json(['error' => 'Réception non trouvée.'], 404);
        }

        if ($req->filled('statut') && !$reception->canTransitionTo($req->statut)) {
            return response()->json([
                'error' => "Transition de statut interdite : de '{$reception->statut}' vers '{$req->statut}'."
            ], 422);
        }

        $authUser = $user;
        $oldData = $reception->toArray();

        $reception->update($req->only([
            'vehicule_id',
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
            $authUser = $request->user();

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

        if (!$reception->canTransitionTo('validee')) {
            return response()->json([
                'message' => "Impossible de valider cette réception : transition interdite depuis '{$reception->statut}'."
            ], 422);
        }

        DB::beginTransaction();

        try {

            $authUser = $request->user();

            $vehicule = $reception->vehicule;

            // 1. Valider la réception
            $reception->update([
                'statut' => 'validee',
                'validated_by_id' => $authUser->id,
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
        $user = $request->user();

        try {
            // 1. Récupérer la réception et la réparation
            $reception = Reception::with('vehicule')->findOrFail($id);

            $reparation = Reparation::where('reception_id', $reception->id)
                ->firstOrFail();

            // 2. Vérifier que la réparation n'est pas déjà terminée
            if ($reparation->statut === 'termine') {
                return response()->json([
                    'message' => 'Cette réparation est déjà terminée.',
                    'toast' => 'warning'
                ], 400);
            }

            // 3. Vérifier que tous les outils ont été restitués
            $unreturnedTools = PretOutil::where('reparation_id', $reparation->id)
                ->where('statut', 'prete')
                ->exists();

            if ($unreturnedTools) {
                return response()->json([
                    'message' => 'Impossible de terminer la réparation : tous les outils prêtés doivent être restitués.',
                    'toast' => 'warning'
                ], 422);
            }

            // 4. Transaction uniquement pour les modifications
            DB::beginTransaction();

            // 5. Marquer la réparation comme terminée
            $reparation->update([
                'user_id' => $user->id,
                'statut' => 'termine',
            ]);

            // 6. Enregistrer qui a terminé les travaux
            $reception->update([
                'repaired_by_id' => $user->id,
            ]);

            // 7. Créer la facture uniquement si elle n'existe pas déjà
            $facture = Facture::firstOrCreate(
                [
                    'reception_id' => $reception->id,
                ],
                [
                    'montant' => 0,
                    'date_generation' => null,
                    'statut' => 'en_attente',
                    'recu' => null,
                    'user_id' => null,
                ]
            );

            // 8. Log
            Log::create([
                'idUser' => $user->id,
                'user_nom' => $user->last_name,
                'user_prenom' => $user->first_name,
                'user_pseudo' => $user->pseudo,
                'user_role' => $user->role,
                'user_doc' => $user->created_at,
                'action' => 'update',
                'table_concernee' => 'reparations',
                'details' => "Réparation terminée pour le véhicule {$reception->vehicule->immatriculation} (Réception ID : {$reception->id}).",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Réparation terminée avec succès.',
                'facture' => $facture,
                'toast' => 'success',
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la terminaison de la réparation : ' . $e->getMessage(),
                'toast' => 'danger',
            ], 500);
        }
    }






}
