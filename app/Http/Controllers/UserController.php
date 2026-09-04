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
        $actingUser = $req->user();

        if ($actingUser) {
            Log::create([
                'idUser' => $actingUser->id,
                'user_nom' => $actingUser->last_name,
                'user_prenom' => $actingUser->first_name,
                'user_pseudo' => $actingUser->pseudo,
                'user_role' => $actingUser->role,
                'user_doc' => $actingUser->created_at,
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
    function listeUser(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('pseudo', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        $users = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ], 200);
    }

    // Fonction pour supprimer un user par son ID
    public function deleteUser(Request $request, $id)
    {
        try {
            $authUser = $request->user();

            if (!$authUser) {
                return response()->json(['status' => 'Erreur : Utilisateur non authentifié.'], 401);
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

    // Fonction pour supprimer plusieurs utilisateurs simultanément (suppression groupée)
    public function deleteUsersMultiple(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:users,id',
            ]);

            $authUser = $request->user();

            $ids = array_filter($request->input('ids'), fn($id) => (int) $id !== (int) $authUser->id);
            $deletedCount = User::whereIn('id', $ids)->delete();

            if ($authUser && $deletedCount > 0) {
                Log::create([
                    'idUser' => $authUser->id,
                    'user_nom' => $authUser->last_name,
                    'user_prenom' => $authUser->first_name,
                    'user_pseudo' => $authUser->pseudo,
                    'user_role' => $authUser->role,
                    'user_doc' => $authUser->created_at,
                    'action' => 'delete',
                    'table_concernee' => 'users',
                    'details' => "Suppression groupée de {$deletedCount} utilisateur(s) (IDs: " . implode(', ', $ids) . ")",
                    'created_at' => now(),
                ]);
            }

            return response()->json([
                'status' => 'deleted',
                'count' => $deletedCount,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la suppression.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Fonction pour mettre à jour les informations d'un user
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

        $authUser = $req->user();
        if (!$authUser) {
            return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
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
            if ($authUser->role !== 'admin') {
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
                $modifications[] = match ($key) {
                    'first_name' => "Prénom modifié",
                    'last_name' => "Nom modifié",
                    'pseudo' => "Pseudo modifié",
                    default => ucfirst($key) . " modifié",
                };
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

    public function logout(Request $request)
    {
        // Vérifie que Sanctum voit bien ton user
        // \Log::info('User dans logout', ['user' => $request->user()]);

        /* Si tu veux juste supprimer le dernier token (celui utilisé pour la requête actuelle)
            * (utile si tu veux garder d'autres sessions actives sur d'autres appareils)
            $token = $request->user()->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }
        */

        $user = $request->user();
        if ($user) {
            $user->tokens()->delete(); // supprime tous les tokens du user
        }

        return response()->json(['message' => 'Déconnecté']);
    }

}
