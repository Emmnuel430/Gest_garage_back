<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Garage;
use App\Models\Log;
use App\Models\User;

class GarageController extends Controller
{
    // Ajouter un nouveau garage
    public function addGarage(Request $req)
    {
        $req->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'admin_id' => 'required|integer|exists:users,id',
        ]);

        $garage = new Garage;
        $garage->nom = $req->input('nom');
        $garage->adresse = $req->input('adresse');
        $garage->save();

        $admin = User::find($req->input('admin_id'));

        if ($admin) {
            Log::create([
                'idUser' => $admin->id,
                'user_nom' => $admin->last_name,
                'user_prenom' => $admin->first_name,
                'user_pseudo' => $admin->pseudo,
                'user_role' => $admin->role,
                'user_doc' => $admin->created_at,
                'action' => 'add',
                'table_concernee' => 'garages',
                'details' => "Garage ajouté : {$garage->nom} (ID: {$garage->id})",
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Garage ajouté avec succès',
            'garage' => $garage,
        ], 201);
    }

    // Récupérer un garage par ID
    public function getGarage($id)
    {
        $garage = Garage::find($id);
        if (!$garage) {
            return response()->json(['error' => 'Garage non trouvé.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'garage' => $garage,
        ], 200);
    }

    // Liste de tous les garages
    public function listeGarage()
    {
        $garages = Garage::all();
        if ($garages->isEmpty()) {
            return response()->json(['status' => 'Aucun garage trouvé.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'garages' => $garages,
        ], 200);
    }

    // Supprimer un garage
    public function deleteGarage(Request $request, $id)
    {
        try {
            $authUserId = $request->query('user_id');
            $authUser = User::find($authUserId);

            if (!$authUser) {
                return response()->json(['status' => 'Erreur : ID utilisateur invalide.'], 400);
            }

            $garage = Garage::find($id);
            if (!$garage) {
                return response()->json(['status' => 'Garage introuvable'], 404);
            }

            $garageNom = $garage->nom;
            $garage->delete();

            Log::create([
                'idUser' => $authUser->id,
                'user_nom' => $authUser->last_name,
                'user_prenom' => $authUser->first_name,
                'user_pseudo' => $authUser->pseudo,
                'user_role' => $authUser->role,
                'user_doc' => $authUser->created_at,
                'action' => 'delete',
                'table_concernee' => 'garages',
                'details' => "Garage supprimé : {$garageNom} (ID: {$id})",
                'created_at' => now(),
            ]);

            return response()->json(['status' => 'deleted'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la suppression.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateGarage(Request $req, $id)
    {
        $req->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $garage = Garage::find($id);
        if (!$garage) {
            return response()->json(['error' => 'Garage non trouvé.'], 404);
        }

        $authUser = User::find($req->input('user_id'));
        if (!$authUser) {
            return response()->json(['error' => 'Utilisateur authentifié invalide.'], 400);
        }

        $oldData = $garage->toArray();

        $garage->nom = $req->input('nom', $garage->nom);
        $garage->adresse = $req->input('adresse', $garage->adresse);
        $garage->save();

        $fieldsToIgnore = ['updated_at', 'created_at'];
        $newData = $garage->toArray();
        $modifications = [];

        foreach ($newData as $key => $value) {
            if (in_array($key, $fieldsToIgnore))
                continue;

            if (array_key_exists($key, $oldData) && $oldData[$key] != $value) {
                switch ($key) {
                    case 'nom':
                        $modifications[] = "Nom modifié";
                        break;
                    case 'adresse':
                        $modifications[] = "Adresse modifiée";
                        break;
                    default:
                        $modifications[] = ucfirst($key) . " modifié";
                }
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
                'table_concernee' => 'garages',
                'details' => "Changements sur Garage ID {$garage->id} : " . implode(", ", $modifications),
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Garage mis à jour avec succès.',
            'garage' => $garage,
        ], 200);
    }

}
