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
        $userId = $request->input('user_id');
        $authUser = User::find($userId);
        // Récupération de l'utilisateur connecté
        $reception = Reception::find($id);
        $vehicule = $reception->vehicule;

        if (!$authUser) {
            return response()->json(['message' => 'Utilisateur introuvable'], 404);
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
        $userId = $request->input('user_id');
        $authUser = User::find($userId);
        $reception = Reception::find($id);
        $vehicule = $reception->vehicule;

        if (!$authUser) {
            return response()->json(['message' => 'Utilisateur introuvable'], 404);
        }

        $chrono = Chrono::where('reception_id', $id)->first();

        if (!$chrono || $chrono->end_time) {
            return response()->json(['message' => 'Chrono introuvable ou déjà terminé'], 404);
        }

        if ($chrono->pause_time) {
            return response()->json(['message' => 'Le chrono est déjà en pause'], 400);
        }

        $chrono->pause_time = now();
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
        $userId = $request->input('user_id');
        $authUser = User::find($userId);
        $reception = Reception::find($id);
        $vehicule = $reception->vehicule;

        if (!$authUser) {
            return response()->json(['message' => 'Utilisateur introuvable'], 404);
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


    public function listeChronos()
    {
        $chronos = Chrono::with('reception.vehicule')->get();

        return response()->json($chronos);
    }


}
