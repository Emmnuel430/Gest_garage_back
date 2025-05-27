<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicule;
use App\Models\Reception;
use App\Models\Reparation;
use App\Models\Facture;
use App\Models\Mecanicien;
use App\Models\Log;
use App\Models\Chrono;

class DashboardController extends Controller
{
    public function index()
    {
        $allReceptions = Reception::all();

        if ($allReceptions->isEmpty()) {
            $receptionsAttente = "0";
            $receptionsValidee = "0";
        } else {
            $receptionsAttenteCount = $allReceptions->where('statut', 'attente')->count();
            $receptionsValideeCount = $allReceptions->where('statut', 'validee')->count();

            $receptionsAttente = $receptionsAttenteCount > 0
                ? $receptionsAttenteCount
                : "✅";

            $receptionsValidee = $receptionsValideeCount > 0
                ? $receptionsValideeCount
                : "✅";
        }
        // --------------
        $vehicules = Vehicule::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $receptions = Reception::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        // ----------------
        $latestReceptions = Reception::with('vehicule', 'gardien', )
            // ->where('statut', 'attente')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            // Statistiques générales
            'benefice_total' => Facture::where('statut', 'payee')->sum('montant'),
            'mecaniciens_total' => Mecanicien::count(),
            'reparations_en_cours' => Reparation::where('statut', 'en_cours')->count(),
            'reparations_terminees' => Reparation::where('statut', 'termine')->count(),
            'temps_moyen_reparation' => Chrono::whereNotNull('end_time')->avg('duree_total'),
            'receptions_attente' => $receptionsAttente,
            'receptions_validee' => $receptionsValidee,
            'receptions_terminee' => Reception::where('statut', 'termine')->count(),
            // -------
            'vehicules_total' => $vehicules,
            'receptions_total' => $receptions,
            // -------
            'latest_receptions' => $latestReceptions,
            // -------
            'latest_factures_impayees' => Facture::with('reception.vehicule')
                ->where('statut', 'en_attente')
                ->latest()
                ->take(5)
                ->get(),
            'latest_logs' => Log::latest()->take(6)->get(),
            'latest_chronos_en_cours' => Chrono::whereNull('end_time')
                ->latest()
                ->take(5)
                ->get(),

            /* 'factures_total' => Facture::count(),
            'garages_total' => Garage::count(), */

        ]);
    }

}
