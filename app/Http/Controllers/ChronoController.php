<?php
namespace App\Http\Controllers;

use App\Models\Chrono;
use App\Models\Reception;
use App\Models\Log;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

class ChronoController extends Controller
{
    public function stopChrono(Request $request, $id)
    {
        $authUser = $request->user();
        // Récupération de l'utilisateur connecté
        $reception = Reception::find($id);
        $vehicule = $reception->vehicule;

        if (!$authUser) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }


        $chrono = Chrono::where('reception_id', $id)->first();

        if (!$chrono || $chrono->end_time) {
            return response()->json(['message' => 'Chrono introuvable ou déjà arrêté.'], 404);
        }

        $chrono->end_time = now();

        // Calcul de la durée (en minutes)
        $start = Carbon::parse($chrono->start_time);
        $end = Carbon::parse($chrono->end_time);
        $duration = $start->diffInMinutes($end);

        $chrono->duree_total = $duration;
        $chrono->save();



        // Création du log
        Log::create([
            'idUser' => $authUser->id,
            'user_nom' => $authUser->last_name,
            'user_prenom' => $authUser->first_name,
            'user_pseudo' => $authUser->pseudo,
            'user_role' => $authUser->role,
            'user_doc' => $authUser->created_at,
            'action' => 'delete',
            'table_concernee' => 'chronos',
            'details' => "Chrono arrêté pour le véhicule {$vehicule->immatriculation} (Réception ID : {$reception->id}) - Durée : {$duration} minutes",
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Chrono arrêté avec succès.',
            'durée_en_minutes' => $duration
        ]);
    }

    public function pauseChrono(Request $request, $id)
    {
        $authUser = $request->user();
        $reception = Reception::find($id);
        $vehicule = $reception->vehicule;

        if (!$authUser) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        $chrono = Chrono::where('reception_id', $id)->first();

        if (!$chrono || $chrono->end_time) {
            return response()->json(['message' => 'Chrono introuvable ou déjà terminé'], 404);
        }

        if ($chrono->pause_time) {
            return response()->json(['message' => 'Le chrono est déjà en pause'], 400);
        }

        $chrono->pause_time = now();
        $chrono->statut = 'en_pause';
        $chrono->save();

        Log::create([
            'idUser' => $authUser->id,
            'user_nom' => $authUser->last_name,
            'user_prenom' => $authUser->first_name,
            'user_pseudo' => $authUser->pseudo,
            'user_role' => $authUser->role,
            'user_doc' => $authUser->created_at,
            'action' => 'pause',
            'table_concernee' => 'chronos',
            'details' => "Chrono mis en pause pour le véhicule {$vehicule->immatriculation} (Réception ID : {$reception->id})",
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Chrono mis en pause.']);
    }

    public function resumeChrono(Request $request, $id)
    {
        $authUser = $request->user();
        $reception = Reception::find($id);
        $vehicule = $reception->vehicule;

        if (!$authUser) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        $chrono = Chrono::where('reception_id', $id)->first();

        if (!$chrono || $chrono->end_time) {
            return response()->json(['message' => 'Chrono introuvable ou déjà terminé'], 404);
        }

        if (!$chrono->pause_time) {
            return response()->json(['message' => 'Le chrono n’est pas en pause'], 400);
        }

        $pauseDuration = Carbon::parse($chrono->pause_time)->diffInMinutes(now());

        $chrono->temps_total_pause += $pauseDuration;
        $chrono->pause_time = null;
        $chrono->statut = 'en_cours';
        $chrono->resume_time = now();
        $chrono->save();

        Log::create([
            'idUser' => $authUser->id,
            'user_nom' => $authUser->last_name,
            'user_prenom' => $authUser->first_name,
            'user_pseudo' => $authUser->pseudo,
            'user_role' => $authUser->role,
            'user_doc' => $authUser->created_at,
            'action' => 'resume',
            'table_concernee' => 'chronos',
            'details' => "Chrono repris pour le véhicule {$vehicule->immatriculation} (Réception ID : {$reception->id}) après {$pauseDuration} min de pause.",
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Chrono repris.']);
    }


    public function listeChronos(Request $request)
{
    // 1. Initialise la requête avec les relations imbriquées nécessaires
    $query = Chrono::with('reception.vehicule');

    // 2. Filtre par recherche textuelle (recherche dans les relations imbriquées du véhicule)
    if ($request->filled('search')) {
        $search = $request->query('search');
        
        $query->whereHas('reception.vehicule', function ($q) use ($search) {
            $q->where('immatriculation', 'like', "%{$search}%")
              ->orWhere('marque', 'like', "%{$search}%")
              ->orWhere('modele', 'like', "%{$search}%");
        });
    }

    // 4. Tri par date de création du chrono et pagination standardisée à 15 éléments
    $chronos = $query->orderByDesc('created_at')->paginate(15);

    $chronosEnCours = Chrono::with('reception.vehicule')
        ->whereNull('end_time')
        ->orderByDesc('created_at')
        ->get();

    // 5. Structure de réponse JSON identique pour faciliter l'intégration côté React
    return response()->json([
        'status' => 'success',
        'chronos' => $chronos->items(),
        'chronos_en_cours' => $chronosEnCours,
        'pagination' => [
            'current_page' => $chronos->currentPage(),
            'per_page' => $chronos->perPage(),
            'total' => $chronos->total(),
            'last_page' => $chronos->lastPage(),
        ],
    ], 200);
}



}
