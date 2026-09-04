<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BilletSortie;
use App\Models\Reception;

use App\Models\Log;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class BilletSortieController extends Controller
{
    public function listeBilletSortie(Request $request)
    {
        $query = BilletSortie::with([
            'reception.vehicule.mecanicien',
            'reception.chrono',
            'user',
            'chefAtelier'
        ]);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('reception.vehicule', function ($q) use ($search) {
                $q->where('immatriculation', 'like', "%{$search}%")
                    ->orWhere('marque', 'like', "%{$search}%")
                    ->orWhere('modele', 'like', "%{$search}%");
            });
        }

        $billetSortie = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'billets_sortie' => $billetSortie->items(),
            'pagination' => [
                'current_page' => $billetSortie->currentPage(),
                'per_page' => $billetSortie->perPage(),
                'total' => $billetSortie->total(),
                'last_page' => $billetSortie->lastPage(),
            ],
        ]);
    }

    public function genererBilletSortie(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $reception = Reception::findOrFail($id);
            $vehicule = $reception->vehicule;
            $user = $request->user();

            $reception->update([
                'statut' => 'termine', // à adapter selon ton enum ou valeur
            ]);
            // Création du billet
            $billet = BilletSortie::create([
                'reception_id' => $reception->id,
                'user_id' => $user->id,
                'date_generation' => now(),
            ]);

            $pdf = PDF::loadView('pdf.fiche_sortie_vehicule', [
                'reception' => $reception,
                'user' => $user,
                'chefAtelier' => $user,
                'billetSortie' => $billet,
            ]);

            $pdfName = 'fiche_sortie_vehicule_' . $reception->id . '.pdf';
            $pdfPath = 'billets_sortie/' . $pdfName;
            Storage::put('public/' . $pdfPath, contents: $pdf->output());

            $billet->update(['fiche_sortie_vehicule' => $pdfPath]);

            // Log
            Log::create([
                'idUser' => $user->id,
                'user_nom' => $user->last_name,
                'user_prenom' => $user->first_name,
                'user_pseudo' => $user->pseudo,
                'user_role' => $user->role,
                'user_doc' => $user->created_at,
                'action' => 'create',
                'table_concernee' => 'billets sortie',
                'details' => "Billet de sortie généré pour le véhicule {$vehicule->immatriculation} (Réception ID : {$reception->id})",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Billet de sortie généré !',
                'billet_sortie' => $billet,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }
}
