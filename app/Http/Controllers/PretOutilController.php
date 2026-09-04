<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PretOutil;
use App\Models\Outil;
use App\Models\Reparation;
use App\Models\Facture;
use App\Models\User;
use App\Models\Mecanicien;
use App\Models\Log;
use Illuminate\Support\Facades\DB;

class PretOutilController extends Controller
{
    public function index(Request $request)
    {
        $query = PretOutil::with(['outil', 'reparation.reception.vehicule', 'mecanicien']);

        if ($request->has('reparation_id')) {
            $query->where('reparation_id', $request->query('reparation_id'));
        }

        if ($request->has('mecanicien_id')) {
            $query->where('mecanicien_id', $request->query('mecanicien_id'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('search')) {
            $query->whereHas('reparation.reception.vehicule', function ($q) use ($request) {
                $search = $request->query('search');
                $q->where('immatriculation', 'like', "%{$search}%")
                    ->orWhere('marque', 'like', "%{$search}%")
                    ->orWhere('modele', 'like', "%{$search}%");
            });
        }

        $prets = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'prets' => $prets->items(),
            'pagination' => [
                'current_page' => $prets->currentPage(),
                'per_page' => $prets->perPage(),
                'total' => $prets->total(),
                'last_page' => $prets->lastPage(),
            ],
        ]);
    }

    public function preteOutil(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->cannot('gerer-outils')) {
            return response()->json(['error' => 'Non autorisé. Seul le Gérant ou la Caisse Outils peut prêter des outils.'], 403);
        }

        $validated = $request->validate([
            'mecanicien_id' => 'required|exists:mecaniciens,id',
            'reparation_id' => 'required|exists:reparations,id',
            'outil_id' => 'required|exists:outils,id',
            'quantite' => 'nullable|integer|min:1',
            'est_partage' => 'nullable|boolean',
            'partager_tous_vehicules' => 'nullable|boolean',
            'parent_pret_id' => 'nullable|exists:pret_outils,id'
        ]);

        $mecanicien = Mecanicien::findOrFail($validated['mecanicien_id']);
        $outil = Outil::findOrFail($validated['outil_id']);
        $reparation = Reparation::with('reception.vehicule.mecanicien')->findOrFail($validated['reparation_id']);

        $vehicule = $reparation->reception?->vehicule;
        if (!$vehicule) {
            return response()->json(['error' => 'Aucun véhicule associé à cette réparation.'], 422);
        }

        // 1. Sécurité : Vérification que le véhicule appartient bien au mécanicien sélectionné
        if ((int) $vehicule->mecanicien_id !== (int) $mecanicien->id) {
            return response()->json([
                'error' => "Le véhicule sélectionné ({$vehicule->immatriculation}) n'est pas assigné au mécanicien {$mecanicien->prenom} {$mecanicien->nom}."
            ], 422);
        }

        // 2. Vérification du statut de la réparation et de la réception
        $reception = $reparation->reception;
        if (!$reception || in_array($reception->statut, ['termine'])) {
            return response()->json(['error' => 'Un prêt d\'outil est autorisé uniquement pour une réparation active (non terminée).'], 422);
        }

        if ($reparation->statut === 'termine') {
            return response()->json(['error' => 'Cette réparation est déjà marquée comme terminée.'], 422);
        }

        $quantite = $validated['quantite'] ?? 1;
        $estPartage = (bool) ($validated['est_partage'] ?? false);
        $partagerTousVehicules = (bool) ($validated['partager_tous_vehicules'] ?? false);

        DB::beginTransaction();
        try {
            // Vérifier si le mécanicien détient déjà cet outil en cours d'emprunt
            $existingActiveLoan = PretOutil::where('mecanicien_id', $mecanicien->id)
                ->where('outil_id', $outil->id)
                ->where('statut', 'prete')
                ->first();

            $parentPretId = $validated['parent_pret_id'] ?? null;

            if ($existingActiveLoan && $estPartage) {
                // Le mécanicien possède déjà l'outil physiquement, utilisation successive sans décrémenter le stock
                $parentPretId = $parentPretId ?? $existingActiveLoan->id;
            } else {
                // Sortie physique du stock : vérifier et décrémenter
                if ($outil->quantite < $quantite) {
                    return response()->json([
                        'error' => "Quantité insuffisante en stock pour l'outil '{$outil->libelle}'. Disponible : {$outil->quantite}."
                    ], 400);
                }
                $outil->decrement('quantite', $quantite);
            }

            // Création du prêt principal
            $pret = PretOutil::create([
                'outil_id' => $outil->id,
                'reparation_id' => $reparation->id,
                'mecanicien_id' => $mecanicien->id,
                'quantite' => $quantite,
                'statut' => 'prete',
                'est_partage' => $estPartage || $partagerTousVehicules,
                'parent_pret_id' => $parentPretId
            ]);

            // Si l'utilisateur a choisi de partager automatiquement avec tous ses véhicules en cours
            $sharedCount = 0;
            if ($partagerTousVehicules) {
                $otherReparations = Reparation::where('statut', 'en_cours')
                    ->where('id', '!=', $reparation->id)
                    ->whereHas('reception.vehicule', function ($q) use ($mecanicien) {
                        $q->where('mecanicien_id', $mecanicien->id);
                    })
                    ->get();

                foreach ($otherReparations as $otherRep) {
                    $alreadyLoaned = PretOutil::where('reparation_id', $otherRep->id)
                        ->where('outil_id', $outil->id)
                        ->where('statut', 'prete')
                        ->exists();

                    if (!$alreadyLoaned) {
                        PretOutil::create([
                            'outil_id' => $outil->id,
                            'reparation_id' => $otherRep->id,
                            'mecanicien_id' => $mecanicien->id,
                            'quantite' => $quantite,
                            'statut' => 'prete',
                            'est_partage' => true,
                            'parent_pret_id' => $pret->id
                        ]);
                        $sharedCount++;
                    }
                }
            }

            $detailLog = "Outil '{$outil->libelle}' prêté pour la réparation ID {$reparation->id} (Véhicule: {$vehicule->immatriculation}, Mécanicien: {$mecanicien->prenom} {$mecanicien->nom})";
            if ($estPartage || $partagerTousVehicules) {
                $detailLog .= " [PARTAGÉ" . ($sharedCount > 0 ? " sur +{$sharedCount} véhicule(s)" : "") . "]";
            }

            $this->logAction($user, 'add', 'pret_outils', $detailLog);

            DB::commit();

            return response()->json([
                'message' => 'Outil prêté avec succès' . ($sharedCount > 0 ? " et partagé sur {$sharedCount} autre(s) véhicule(s)." : "."),
                'pret' => $pret->load('outil', 'reparation.reception.vehicule', 'mecanicien')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Une erreur est survenue lors du prêt: ' . $e->getMessage()], 500);
        }
    }

    public function restitueOutil(Request $request, $id)
    {
        $user = $request->user();

        if (!$user || $user->cannot('gerer-outils')) {
            return response()->json([
                'error' => 'Non autorisé. Seul le Gérant ou la Caisse Outils peut restituer des outils.'
            ], 403);
        }

        $pret = PretOutil::with(['outil', 'reparation.reception.vehicule', 'mecanicien'])->findOrFail($id);

        if ($pret->statut === 'restitue') {
            return response()->json([
                'error' => 'Cet outil a déjà été restitué.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // 1. Restitution du prêt
            $pret->update([
                'statut' => 'restitue',
            ]);

            // 2. Vérifier s'il reste d'autres prêts actifs de cet outil pour ce mécanicien
            $remainingActiveLoans = PretOutil::where('mecanicien_id', $pret->mecanicien_id)
                ->where('outil_id', $pret->outil_id)
                ->where('statut', 'prete')
                ->where('id', '!=', $pret->id)
                ->count();

            // 3. Remise en stock si le prêt n'était pas partagé, ou si c'est le dernier prêt actif pour ce mécanicien
            $stockReintegre = false;
            if (!$pret->est_partage || $remainingActiveLoans === 0) {
                $pret->outil->increment('quantite', $pret->quantite);
                $stockReintegre = true;
            }

            // 4. Log
            $vehiculeImmat = $pret->reparation?->reception?->vehicule?->immatriculation ?? "N/A";
            $detailLog = "Outil '{$pret->outil->libelle}' restitué pour le véhicule {$vehiculeImmat} (Mécanicien: {$pret->mecanicien?->prenom} {$pret->mecanicien?->nom})";
            if ($pret->est_partage && $remainingActiveLoans > 0) {
                $detailLog .= " [Prêt partagé : encore actif sur {$remainingActiveLoans} autre(s) réparation(s)]";
            } elseif ($stockReintegre) {
                $detailLog .= " [Stock réintégré]";
            }

            $this->logAction(
                $user,
                'update',
                'pret_outils',
                $detailLog
            );

            DB::commit();

            return response()->json([
                'message' => 'Outil restitué avec succès.',
                'pret' => $pret->fresh(['outil', 'reparation.reception.vehicule', 'mecanicien']),
                'stock_reintegre' => $stockReintegre,
                'remaining_active_loans' => $remainingActiveLoans,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Une erreur est survenue lors de la restitution : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a tool loan (edit quantity, recalculate available stock).
     * Only admin and caisse_outils roles are authorized.
     */
    public function updatePret(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->cannot('gerer-outils')) {
            return response()->json(['error' => 'Non autorisé. Seul le Gérant ou la Caisse Outils peut modifier un prêt.'], 403);
        }

        $validated = $request->validate([
            'quantite_pretee' => 'required|integer|min:1',
        ]);

        $pret = PretOutil::with('outil')->findOrFail($id);

        if ($pret->statut === 'restitue') {
            return response()->json(['error' => 'Impossible de modifier un prêt déjà restitué.'], 400);
        }

        $ancienneQuantite = $pret->quantite_pretee ?? $pret->quantite;
        $nouvelleQuantite = $validated['quantite_pretee'];
        $diff = $nouvelleQuantite - $ancienneQuantite;

        DB::beginTransaction();
        try {
            // Adjust available stock: if increasing loan quantity, decrement stock; if decreasing, increment stock
            if ($diff !== 0 && !$pret->est_partage) {
                if ($diff > 0) {
                    // Increasing loan: check stock availability
                    if ($pret->outil->quantite < $diff) {
                        return response()->json([
                            'error' => "Stock insuffisant pour augmenter la quantité prêtée. Disponible : {$pret->outil->quantite}"
                        ], 400);
                    }
                    $pret->outil->decrement('quantite', $diff);
                } else {
                    // Decreasing loan: return stock
                    $pret->outil->increment('quantite', abs($diff));
                }
            }

            $pret->update([
                'quantite_pretee' => $nouvelleQuantite,
                'quantite' => $nouvelleQuantite,
            ]);

            $this->logAction(
                $user,
                'update',
                'pret_outils',
                "Prêt ID {$pret->id} modifié : quantité {$ancienneQuantite} → {$nouvelleQuantite} pour l'outil '{$pret->outil->libelle}'"
            );

            DB::commit();

            return response()->json([
                'message' => 'Prêt mis à jour avec succès.',
                'pret' => $pret->load('outil'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la modification du prêt : ' . $e->getMessage()], 500);
        }
    }

    private function logAction($user, $action, $table, $details)
    {
        if ($user) {
            Log::create([
                'idUser' => $user->id,
                'user_nom' => $user->last_name,
                'user_prenom' => $user->first_name,
                'user_pseudo' => $user->pseudo,
                'user_role' => $user->role,
                'user_doc' => $user->created_at,
                'action' => $action,
                'table_concernee' => $table,
                'details' => $details,
            ]);
        }
    }
}
