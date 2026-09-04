<?php

namespace App\Observers;

use App\Models\Mecanicien;
use App\Models\User;
use App\Models\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class MecanicienObserver
{
    /**
     * Handle the Mecanicien "created" event.
     */
    public function created(Mecanicien $mecanicien): void
    {
        // Génération du PDF
        $pdf = Pdf::loadView('pdf.fiche_mecanicien', compact('mecanicien'));
        $pdfPath = 'fiches_mecaniciens/fiche_' . $mecanicien->id . '.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());

        // On utilise sans événement pour éviter une boucle infinie lors du update
        $mecanicien->updateQuietly(['fiche_enrolement' => $pdfPath]);

        // Récupération de l'admin (via la relation ou l'ID transmis)
        $admin = User::find($mecanicien->user_id) ?? User::first();

        if ($admin) {
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
        }
    }

    /**
     * Handle the Mecanicien "updated" event.
     */
    public function updated(Mecanicien $mecanicien): void
    {
        //
    }

    /**
     * Handle the Mecanicien "deleted" event.
     */
    public function deleted(Mecanicien $mecanicien): void
    {
        //
    }

    /**
     * Handle the Mecanicien "restored" event.
     */
    public function restored(Mecanicien $mecanicien): void
    {
        //
    }

    /**
     * Handle the Mecanicien "force deleted" event.
     */
    public function forceDeleted(Mecanicien $mecanicien): void
    {
        //
    }
}
