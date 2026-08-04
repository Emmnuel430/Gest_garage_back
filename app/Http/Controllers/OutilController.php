<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Outil;
use App\Models\User;
use App\Models\Log;

class OutilController extends Controller
{
    public function index()
    {
        return response()->json(Outil::all());
    }

    public function store(Request $request)
    {
        $user = $request->user() ?: User::find($request->input('user_id'));
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Non autorisé. Seul le Gérant peut gérer l\'inventaire.'], 403);
        }

        $validated = $request->validate([
            'libelle' => 'required|string|max:255',
            'quantite' => 'required|integer|min:0',
            'reference' => 'required|string|unique:outils,reference',
        ]);

        $outil = Outil::create($validated);

        $this->logAction($user, 'create', 'outils', "Ajout de l'outil: {$outil->libelle} (Réf: {$outil->reference}, Qté: {$outil->quantite})");

        return response()->json([
            'message' => 'Outil ajouté avec succès.',
            'outil' => $outil
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user() ?: User::find($request->input('user_id'));
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Non autorisé. Seul le Gérant peut gérer l\'inventaire.'], 403);
        }

        $outil = Outil::findOrFail($id);

        $validated = $request->validate([
            'libelle' => 'required|string|max:255',
            'quantite' => 'required|integer|min:0',
            'reference' => 'required|string|unique:outils,reference,' . $id,
        ]);

        $oldQuantite = $outil->quantite;
        $outil->update($validated);

        $this->logAction($user, 'update', 'outils', "Modification de l'outil ID {$outil->id}: {$outil->libelle} (Qté: {$oldQuantite} -> {$outil->quantite})");

        return response()->json([
            'message' => 'Outil mis à jour avec succès.',
            'outil' => $outil
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user() ?: User::find($request->input('user_id'));
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Non autorisé. Seul le Gérant peut gérer l\'inventaire.'], 403);
        }

        $outil = Outil::findOrFail($id);
        $libelle = $outil->libelle;
        $reference = $outil->reference;

        $outil->delete();

        $this->logAction($user, 'delete', 'outils', "Suppression de l'outil: {$libelle} (Réf: {$reference})");

        return response()->json([
            'message' => 'Outil supprimé avec succès.'
        ]);
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
