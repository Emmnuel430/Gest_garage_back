<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Mecanicien;
use App\Models\User;
use App\Models\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class MecanicienController extends Controller
{
    // Ajouter un mécanicien
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'type' => 'required|string',
            'vehicules_maitrises' => 'nullable|string',
            'experience' => 'nullable|numeric',
            'contact' => 'nullable|string',
            'contact_urgence' => 'nullable|string',
        ]);

        // Vérification de l'existence du mecanicien

        $existingMecanicien = Mecanicien::where('nom', $validated['nom'])
            ->where('prenom', $validated['prenom'])
            ->first();
        if ($existingMecanicien) {
            return response()->json(['error' => 'Ce mécanicien existe déjà.'], 400);
        }

        $mecanicien = Mecanicien::create(array_merge($validated));

        // Génération du PDF
        $pdf = Pdf::loadView('pdf.fiche_mecanicien', compact('mecanicien'));
        $pdfPath = 'fiches_mecaniciens/fiche_' . $mecanicien->id . '.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());

        $mecanicien->update(['fiche_enrolement' => $pdfPath]);

        // Log
        $admin = User::find($request->input('user_id'));
        Log::create([
            'idUser' => $admin->id,
            'user_nom' => $admin->last_name,
            'user_prenom' => $admin->first_name,
            'user_pseudo' => $admin->pseudo,
            'user_role' => $admin->role,
            'user_doc' => $admin->created_at,
            'action' => 'add',
            'table_concernee' => 'mecaniciens',
            'details' => "Mécanicien ajouté : {$mecanicien->nom} {$mecanicien->prenom} (ID: {$mecanicien->id})",
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Mécanicien ajouté avec succès',
            'mecanicien' => $mecanicien
        ]);
    }

    // Récupérer un mécanicien
    public function show($id)
    {
        $mecanicien = Mecanicien::find($id);
        if (!$mecanicien) {
            return response()->json(['error' => 'Mécanicien non trouvé'], 404);
        }

        return response()->json([
            'status' => 'success',
            'mecanicien' => $mecanicien
        ], 200);
    }

    // Lister tous les mécaniciens
    public function index()
    {
        $mecaniciens = Mecanicien::with(['reparations.reception.vehicule'])->get();

        return response()->json([
            'status' => 'success',
            'mecaniciens' => $mecaniciens
        ]);
    }


    // Mettre à jour un mécanicien
    public function update(Request $request, $id)
    {
        $mecanicien = Mecanicien::find($id);
        if (!$mecanicien) {
            return response()->json(['error' => 'Mécanicien introuvable'], 404);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'nom' => 'sometimes|string',
            'prenom' => 'sometimes|string',
            'type' => 'sometimes|string',
            'vehicules_maitrises' => 'nullable|string',
            'experience' => 'nullable|numeric',
            'contact' => 'nullable|string',
            'contact_urgence' => 'nullable|string',
        ]);

        $mecanicien->update($validated);

        // Regénérer la fiche PDF
        $pdf = Pdf::loadView('pdf.fiche_mecanicien', compact('mecanicien'));
        $pdfPath = 'fiches_mecaniciens/fiche_' . $mecanicien->id . '.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());
        $mecanicien->update(['fiche_enrolement' => $pdfPath]);

        // Log
        $admin = User::find($request->input('user_id'));
        Log::create([
            'idUser' => $admin->id,
            'user_nom' => $admin->last_name,
            'user_prenom' => $admin->first_name,
            'user_pseudo' => $admin->pseudo,
            'user_role' => $admin->role,
            'user_doc' => $admin->created_at,
            'action' => 'update',
            'table_concernee' => 'mecaniciens',
            'details' => "Mécanicien modifié : {$mecanicien->nom} {$mecanicien->prenom} (ID: {$mecanicien->id})",
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Mécanicien mis à jour.',
            'mecanicien' => $mecanicien
        ]);
    }

    // Supprimer un mécanicien
    public function destroy(Request $request, $id)
    {
        $authUser = User::find($request->query('user_id'));
        if (!$authUser) {
            return response()->json(['error' => 'Utilisateur invalide.'], 400);
        }

        $mecanicien = Mecanicien::find($id);
        if (!$mecanicien) {
            return response()->json(['error' => 'Mécanicien non trouvé.'], 404);
        }

        if ($mecanicien->fiche_enrolement)
            Storage::disk('public')->delete($mecanicien->fiche_enrolement);

        $nomComplet = $mecanicien->nom . ' ' . $mecanicien->prenom;
        $mecanicien->delete();

        Log::create([
            'idUser' => $authUser->id,
            'user_nom' => $authUser->last_name,
            'user_prenom' => $authUser->first_name,
            'user_pseudo' => $authUser->pseudo,
            'user_doc' => $authUser->created_at,
            'user_role' => $authUser->role,
            'action' => 'delete',
            'table_concernee' => 'mecaniciens',
            'details' => "Mécanicien supprimé : $nomComplet (ID: {$id})",
            'created_at' => now(),
        ]);

        return response()->json(['status' => 'deleted', 'message' => 'Mécanicien supprimé avec succès.'], 200);
    }
}
