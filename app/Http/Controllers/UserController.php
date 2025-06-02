<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Log;

class UserController extends Controller
{

    // Méthode pour enregistrer un nouvel utilisateur dans la base de données.
    public function addUser(Request $req)
    {
        // Vérifie que le pseudo est unique avant de continuer.
        if (User::where('pseudo', $req->input('pseudo'))->exists()) {
            return response()->json(['error' => 'Le pseudo est déjà utilisé.'], 400);
        }

        // Création d'une nouvelle instance de User
        $user = new User;
        $user->last_name = $req->input('nom');
        $user->first_name = $req->input('prenom');
        $user->pseudo = $req->input('pseudo');
        $user->password = Hash::make($req->input('password'));
        $user->role = $req->input('role');
        $user->save();

        // Enregistrement du log
        $adminId = $req->input('admin_id');
        $admin = User::find($adminId);

        if ($admin) {
            Log::create([
                'idUser' => $admin->id,
                'user_nom' => $admin->last_name,
                'user_prenom' => $admin->first_name,
                'user_pseudo' => $admin->pseudo,
                'user_role' => $admin->role,
                'user_doc' => $admin->created_at,
                'action' => 'create',
                'table_concernee' => 'users',
                'details' => "Nouvel utilisateur ajouté : {$user->last_name} {$user->first_name} (ID: {$user->id}, Role: {$user->role})",
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur créé avec succès',
            'user' => $user->makeHidden('password'),
        ], 201);
    }
    // Fonction pour récupérer un user spécifique par son ID
    function getUser($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
        }
        // Retourne l'user correspondant à l'ID donné
        return response()->json([
            'status' => 'success',
            'user' => $user,
        ], 200);
    }

    // Récuperer tous les Users
    function listeUser()
    {
        // Retourne tous les produits sous forme de collection
        $users = User::all();
        // Retourne la collection d'utilisateurs
        return response()->json([
            'status' => 'success',
            'users' => $users,
        ], 200);
    }

    // Fonction pour supprimer un user par son ID
    public function deleteUser(Request $request, $id)
    {
        try {
            $authUserId = $request->query('user_id'); // ID de l’utilisateur connecté
            $authUser = User::find($authUserId);

            if (!$authUser) {
                return response()->json(['status' => 'Erreur : ID utilisateur invalide.'], 400);
            }

            $user = User::find($id);
            if (!$user) {
                return response()->json(['status' => 'Utilisateur introuvable'], 404);
            }

            $userName = "{$user->last_name} {$user->first_name}";
            $user->delete();

            Log::create([
                'idUser' => $authUser->id,
                'user_nom' => $authUser->last_name,
                'user_prenom' => $authUser->first_name,
                'user_pseudo' => $authUser->pseudo,
                'user_role' => $authUser->role,
                'user_doc' => $authUser->created_at,
                'action' => 'delete',
                'table_concernee' => 'users',
                'details' => "Utilisateur supprimé : {$userName} (ID: {$id})",
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

    // M-à-j les données d'un user
    public function updateUser(Request $req, $id)
    {
        $req->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'pseudo' => 'required|string|max:255|unique:users,pseudo,' . $id,
            'password' => 'nullable|string',
        ]);

        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
        }

        $authUserId = $req->input('user_id');
        $authUser = User::find($authUserId);
        if (!$authUser) {
            return response()->json(['error' => 'Utilisateur authentifié invalide.'], 400);
        }

        $oldData = $user->toArray();

        // Mise à jour
        $user->last_name = $req->input('nom', $user->last_name);
        $user->first_name = $req->input('prenom', $user->first_name);
        $user->pseudo = $req->input('pseudo', $user->pseudo);

        if (
            User::where('pseudo', $req->input('pseudo'))
                ->where('id', '!=', $id)
                ->exists()
        ) {
            return response()->json(['error' => 'Le pseudo est déjà utilisé par un autre utilisateur.'], 400);
        }

        if ($req->has('role')) {
            if ($authUser->role !== 'super_admin') {
                return response()->json(['error' => 'Seul un super administrateur peut modifier le rôle.'], 403);
            }
            $user->role = $req->input('role');
        }


        $passwordChanged = false;
        if ($req->filled('password')) {
            $user->password = Hash::make($req->input('password'));
            $passwordChanged = true;
        }



        $user->save();
        $fieldsToIgnore = ['updated_at', 'created_at'];

        $newData = $user->toArray();
        $modifications = [];
        foreach ($newData as $key => $value) {
            if (in_array($key, $fieldsToIgnore))
                continue;
            if (array_key_exists($key, $oldData) && $oldData[$key] != $value) {
                // Traduction du champ
                switch ($key) {
                    case 'first_name':
                        $modifications[] = "Prénom modifié";
                        break;
                    case 'last_name':
                        $modifications[] = "Nom modifié";
                        break;
                    case 'pseudo':
                        $modifications[] = "Pseudo modifié";
                        break;
                    // Ajoute d'autres cas si besoin
                    default:
                        $modifications[] = ucfirst($key) . " modifié";
                }
            }
        }

        if ($passwordChanged) {
            $modifications[] = "Mot de passe modifié";
        }

        if (count($modifications)) {
            Log::create([
                'idUser' => $authUser->id,
                'user_nom' => $authUser->last_name,
                'user_prenom' => $authUser->first_name,
                'user_pseudo' => $authUser->pseudo,
                'user_role' => $authUser->role,
                'user_doc' => $authUser->created_at,
                'action' => 'maj',
                'table_concernee' => 'users',
                'details' => "Changements effectués sur l'utilisateur (ID: {$user->id}): " . implode(", ", $modifications),
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur mis à jour avec succès.',
            'user' => $user->makeHidden('password'),
        ], 200);
    }
    // Méthode pour connexion
    function login(Request $req)
    {
        // Recherche l'utilisateur en fonction du pseudo fourni.
        $user = User::where('pseudo', $req->pseudo)->first();

        // Vérifie si l'utilisateur existe et si le mot de passe est correct.
        if (!$user || !Hash::check($req->password, $user->password)) {
            return response()->json(['error' => 'Pseudo ou mot de passe incorrect'], 401);
        }

        // Création du token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Retourne les informations de l'utilisateur (sans le mot de passe).
        return response()->json([
            'status' => 'success',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);

    }

}
