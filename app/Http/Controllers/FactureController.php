<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Facture;
use App\Models\User;
use App\Models\Log;
use App\Models\Reception;
use App\Models\Chrono;
use App\Models\Setting;
use App\Models\PretOutil;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\NotificationDispatcher;
use App\Notifications\GarageAlertNotification;

class FactureController extends Controller
{
    public function listeFacture(Request $request)
    {
        $query = Facture::with([
            'reception.vehicule.mecanicien',
            'reception.reparation',
            'reception.chrono',
            'user',
            'caissier',
            'reception.billetSortie.chefAtelier',
        ]);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('reception.vehicule', function ($q) use ($search) {
                $q->where('immatriculation', 'like', "%{$search}%")
                    ->orWhere('marque', 'like', "%{$search}%")
                    ->orWhere('modele', 'like', "%{$search}%");
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        $factures = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'factures' => $factures->items(),
            'pagination' => [
                'current_page' => $factures->currentPage(),
                'per_page' => $factures->perPage(),
                'total' => $factures->total(),
                'last_page' => $factures->lastPage(),
            ],
        ]);
    }

    public function genererFactureEtArreterChrono(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $reception = Reception::findOrFail($id);
            $facture = Facture::where('reception_id', $id)->firstOrFail();
            $chrono = Chrono::where('reception_id', $id)->firstOrFail();
            $vehicule = $reception->vehicule;
            $repairUser = $reception->reparePar ?? $reception->creePar ?? User::find($reception->repaired_by_id) ?? User::first();

            // 1. Arrêt du chrono
            if ($chrono->end_time) {
                return response()->json(['message' => 'Chrono déjà arrêté.'], 400);
            }

            $chrono->end_time = now();

            // Si le chrono était en pause → on met à jour la pause finale
            if ($chrono->pause_time) {
                $tempsPause = Carbon::parse($chrono->pause_time)->diffInMinutes(now());
                $chrono->temps_total_pause += $tempsPause;

                // Remise à zéro
                $chrono->pause_time = null;
                $chrono->resume_time = null;
            }
            $chrono->statut = 'termine';
            $chrono->save();

            // Calcul de la durée nette
            $start = Carbon::parse($chrono->start_time);
            $end = Carbon::parse($chrono->end_time);
            $durationBrut = $start->diffInMinutes($end);
            $durationNet = $durationBrut - $chrono->temps_total_pause;

            // Enregistrer la durée nette
            $chrono->update(['duree_total' => $durationNet]);

            // 2. Calcul du montant
            $tarifHoraire = (int) Setting::get('tarif_horaire', 1000);
            $tarifJournalier = (int) Setting::get('tarif_jours_supp', 2000);
            $nbHeures = ceil($durationNet / 60);
            $coutHoraire = $nbHeures * $tarifHoraire;

            $durationDays = $durationNet / 1440;
            $joursSup = $durationDays > 1 ? (int) ceil($durationDays - 1) : 0;
            $coutJournalier = $joursSup * $tarifJournalier;

            // Calcul des outils spéciaux utilisés pour ce véhicule (facturés par heure selon le chrono du véhicule)
            $outilsSpeciaux = [];
            $totalOutilsSpeciaux = 0;

            if ($reception->reparation) {
                $prets = PretOutil::where('reparation_id', $reception->reparation->id)
                    ->with('outil')
                    ->get();

                $groupedPrets = $prets->filter(function ($pret) {
                    return $pret->outil && $pret->outil->est_special && $pret->outil->prix_par_heure > 0;
                })->groupBy('outil_id');

                foreach ($groupedPrets as $outilId => $pretsGroup) {
                    $firstPret = $pretsGroup->first();
                    $outil = $firstPret->outil;
                    $qte = $pretsGroup->sum(fn($p) => (int) ($p->quantite_pretee ?? $p->quantite ?? 1));
                    $montantLigne = $nbHeures * $outil->prix_par_heure * $qte;
                    $totalOutilsSpeciaux += $montantLigne;
                    $outilsSpeciaux[] = [
                        'libelle' => $outil->libelle,
                        'reference' => $outil->reference,
                        'quantite' => $qte,
                        'prix_par_heure' => $outil->prix_par_heure,
                        'nb_heures' => $nbHeures,
                        'montant' => $montantLigne,
                    ];
                }
            }

            // Facturation sans prix fixe d'entrée : uniquement horaire + jours supp + outils spéciaux
            $montant = $coutHoraire + $coutJournalier + $totalOutilsSpeciaux;

            // 3. Mise à jour de la facture
            $facture->update([
                'montant' => $montant,
                'date_generation' => now(),
                'statut' => 'generee',
            ]);

            // 4. Générer le PDF du reçu
            $pdfRecu = PDF::loadView('pdf.recu_caisse', [
                'reception' => $reception,
                'user' => $repairUser,
                'chefAtelier' => $repairUser,
                'montantHoraire' => $tarifHoraire,
                'montantTotal' => $montant,
                'nbHeures' => $nbHeures,
                'joursSup' => $joursSup,
                'tarifJournalier' => $tarifJournalier,
                'outilsSpeciaux' => $outilsSpeciaux,
                'totalOutilsSpeciaux' => $totalOutilsSpeciaux,
            ]);

            $recuName = 'recu_caisse_' . $reception->id . '.pdf';
            $recuPath = 'recus/' . $recuName;

            Storage::put('public/' . $recuPath, $pdfRecu->output());

            $facture->update(['recu' => $recuPath]);

            // 5. Log de l'action
            $user = $request->user();

            Log::create([
                'idUser' => $user->id,
                'user_nom' => $user->last_name,
                'user_prenom' => $user->first_name,
                'user_pseudo' => $user->pseudo,
                'user_role' => $user->role,
                'user_doc' => $user->created_at,
                'action' => 'update',
                'table_concernee' => 'factures',
                'details' => "Facture générée (montant : {$montant} FCFA) pour le véhicule {$vehicule->immatriculation} (Réception ID : {$reception->id}), chrono arrêté.",
            ]);

            DB::commit();

            // E6: Notification Facture Générée -> admin seulement
            $formattedMontant = number_format($montant, 0, ',', ' ');
            NotificationDispatcher::sendToRoles(
                ['admin'],
                new GarageAlertNotification(
                    title: 'Facture Générée',
                    message: "Facture #{$facture->id} générée pour {$vehicule->immatriculation} — Montant : {$formattedMontant} FCFA.",
                    type: 'info',
                    actionUrl: '/factures'
                )
            );

            return response()->json([
                'message' => 'Facture générée, chrono arrêté !',
                'facture' => $facture,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }


    public function validerPaiement(Request $request, $id)
    {
        $authUser = $request->user();

        if (!$authUser) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        $facture = Facture::find($id);

        if (!$facture || $facture->statut === 'payee') {
            return response()->json(['message' => 'Facture introuvable ou déjà payée.'], 404);
        }

        $facture->statut = 'payee';
        $facture->user_id = $authUser->id;
        $facture->date_paiement = now();
        $facture->save();

        $reception = $facture->reception;
        $vehicule = $reception->vehicule ?? null;

        // Création du log
        Log::create([
            'idUser' => $authUser->id,
            'user_nom' => $authUser->last_name,
            'user_prenom' => $authUser->first_name,
            'user_pseudo' => $authUser->pseudo,
            'user_role' => $authUser->role,
            'user_doc' => $authUser->created_at,
            'action' => 'update',
            'table_concernee' => 'factures',
            'details' => "Paiement validé pour la facture ID {$facture->id} (Montant : {$facture->montant} FCFA) " . ($vehicule ? " - Véhicule : {$vehicule->immatriculation}" : ''),
            'created_at' => now(),
        ]);

        // E7: Notification Paiement Encaissé -> admin seulement
        $formattedMontantPaye = number_format($facture->montant, 0, ',', ' ');
        $immatInfo = $vehicule ? " ({$vehicule->immatriculation})" : "";
        NotificationDispatcher::sendToRoles(
            ['admin'],
            new GarageAlertNotification(
                title: 'Paiement Encaissé',
                message: "Paiement de {$formattedMontantPaye} FCFA validé pour la facture #{$facture->id}{$immatInfo}.",
                type: 'success',
                actionUrl: '/factures'
            )
        );

        return response()->json([
            'message' => 'Paiement validé avec succès.'
        ]);
    }

}
