<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PretOutil;
use App\Models\Outil;
use App\Models\Reparation;
use App\Models\Facture;
use App\Models\User;
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
        
        if ($request->has('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        return response()->json($query->get());
    }

    public function preteOutil(Request $request)
    {
        $user = $request->user() ?: User::find($request->input('user_id'));
        if (!$user || !in_array($user->role, ['admin', 'caisse_outils'])) {
            return response()->json(['error' => 'Non autorisé. Seul le Gérant ou la Caisse Outils peut prêter des outils.'], 403);
        }

        $validated = $request->validate([
            'reparation_id' => 'required|exists:reparations,id',
            'outil_id' => 'required|exists:outils,id',
            'quantite' => 'integer|min:1',
            'est_partage' => 'boolean',
            'parent_pret_id' => 'nullable|exists:pret_outils,id'
        ]);

        $outil = Outil::findOrFail($validated['outil_id']);
        $reparation = Reparation::with('reception.vehicule.mecanicien')->findOrFail($validated['reparation_id']);
        $mecanicien = $reparation->reception->vehicule->mecanicien;

        if (!$mecanicien) {
            return response()->json(['error' => 'Aucun mécanicien n\'est assigné à ce véhicule.'], 400);
        }

        $quantite = $validated['quantite'] ?? 1;
        $estPartage = $validated['est_partage'] ?? false;

        DB::beginTransaction();
        try {
            if (!$estPartage) {
                if ($outil->quantite < $quantite) {
                    return response()->json(['error' => 'Quantité insuffisante en stock.'], 400);
                }
                $outil->decrement('quantite', $quantite);
            }

            $pret = PretOutil::create([
                'outil_id' => $outil->id,
                'reparation_id' => $reparation->id,
                'mecanicien_id' => $mecanicien->id,
                'quantite' => $quantite,
                'statut' => 'prete',
                'est_partage' => $estPartage,
                'parent_pret_id' => $validated['parent_pret_id'] ?? null
            ]);

            $detailLog = "Outil '{$outil->libelle}' prêté pour la réparation ID {$reparation->id} (Mécanicien: {$mecanicien->prenom} {$mecanicien->nom})";
            if ($estPartage) {
                $detailLog .= " [PARTAGÉ/SUCCESSIS]";
            }

            $this->logAction($user, 'create', 'pret_outils', $detailLog);

            DB::commit();

            return response()->json([
                'message' => 'Outil prêté avec succès.',
                'pret' => $pret->load('outil')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Une erreur est survenue lors du prêt: ' . $e->getMessage()], 500);
        }
    }

    public function restitueOutil(Request $request, $id)
    {
        $user = $request->user() ?: User::find($request->input('user_id'));
        if (!$user || !in_array($user->role, ['admin', 'caisse_outils'])) {
            return response()->json(['error' => 'Non autorisé. Seul le Gérant ou la Caisse Outils peut restituer des outils.'], 403);
        }

        $pret = PretOutil::with(['outil', 'reparation.reception'])->findOrFail($id);

        if ($pret->statut === 'restitue') {
            return response()->json(['error' => 'Cet outil a déjà été restitué.'], 400);
        }

        DB::beginTransaction();
        try {
            $pret->update(['statut' => 'restitue']);

            // Si ce n'était pas un prêt partagé, on restitue la quantité dans le stock
            if (!$pret->est_partage) {
                $pret->outil->increment('quantite', $pret->quantite);
            }

            $this->logAction($user, 'update', 'pret_outils', "Outil '{$pret->outil->libelle}' restitué pour la réparation ID {$pret->reparation_id}");

            // Vérification si toutes les réparations ont tous les outils rendus
            $activePrets = PretOutil::where('reparation_id', $pret->reparation_id)
                ->where('statut', 'prete')
                ->exists();

            $autoTerminated = false;
            if (!$activePrets) {
                $reparation = $pret->reparation;
                if ($reparation->statut !== 'termine') {
                    $reparation->update([
                        'chef_atelier_id' => $user->id,
                        'statut' => 'termine'
                    ]);

                    $reception = $reparation->reception;
                    $reception->update([
                        'chef_atelier_id' => $user->id
                    ]);

                    if (!Facture::where('reception_id', $reception->id)->exists()) {
                        Facture::create([
                            'reception_id' => $reception->id,
                            'montant' => 0,
                            'date_generation' => null,
                            'statut' => 'en_attente',
                            'recu' => null,
                            'caissier_id' => null,
                        ]);
                    }

                    $this->logAction($user, 'update', 'reparations', "Réparation ID {$reparation->id} terminée automatiquement suite à la restitution complète des outils.");
                    $autoTerminated = true;
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Outil restitué avec succès.',
                'auto_terminated' => $autoTerminated
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Une erreur est survenue lors de la restitution: ' . $e->getMessage()], 500);
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
