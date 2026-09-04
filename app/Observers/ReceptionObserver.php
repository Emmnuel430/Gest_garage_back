<?php

namespace App\Observers;

use App\Models\Reception;
use App\Models\Log;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReceptionObserver
{
    /**
     * Handle the Reception "created" event.
     */
    public function created(Reception $reception): void
    {
        // 1. Récupération du véhicule associé (via la relation Eloquent)
        $vehicule = $reception->vehicule;

        if ($vehicule) {
            // Génération du PDF de la fiche d’entrée
            $pdf = Pdf::loadView('pdf.fiche_entree_vehicule', compact('reception'));
            $pdfPath = 'vehicules/fiche_entree_' . $vehicule->id . '.pdf';
            Storage::disk('public')->put($pdfPath, $pdf->output());

            // Mise à jour du chemin du PDF sur le véhicule
            $vehicule->update(['fiche_entree_vehicule' => $pdfPath]);
        }

        // 2. Récupération de l'agent à l'origine de la réception
        $gardien = $reception->creePar ?: User::find($reception->created_by_id ?? $reception->gardien_id);

        if ($gardien) {
            // Création automatique du Log d'action
            Log::create([
                'idUser' => $gardien->id,
                'user_nom' => $gardien->last_name,
                'user_prenom' => $gardien->first_name,
                'user_pseudo' => $gardien->pseudo,
                'user_role' => $gardien->role,
                'user_doc' => $gardien->created_at,
                'action' => 'add',
                'table_concernee' => 'receptions',
                'details' => "Réception créée pour le véhicule " . ($vehicule->immatriculation ?? 'Inconnu') . " (Réception ID : {$reception->id})",
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Handle the Reception "updated" event.
     */
    public function updated(Reception $reception): void
    {
        //
    }

    /**
     * Handle the Reception "deleted" event.
     */
    public function deleted(Reception $reception): void
    {
        //
    }

    /**
     * Handle the Reception "restored" event.
     */
    public function restored(Reception $reception): void
    {
        //
    }

    /**
     * Handle the Reception "force deleted" event.
     */
    public function forceDeleted(Reception $reception): void
    {
        //
    }
}
