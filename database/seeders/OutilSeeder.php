<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Outil;
use App\Models\User;
use App\Models\Log;

class OutilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. On récupère ou on crée l'administrateur requis pour les actions de l'inventaire
        // Récupère un admin existant ou en crée un conforme à votre table
        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'admin']);

        // 2. On génère les 15 outils
        Outil::factory()->count(15)->create()->each(function ($outil) use ($admin) {

            // 3. Si vous n'avez pas encore d'Observer pour la table Outils, 
            // on écrit manuellement le log d'ajout ici pour le test :
            Log::create([
                'idUser' => $admin->id,
                'user_nom' => $admin->last_name,
                'user_prenom' => $admin->first_name,
                'user_pseudo' => $admin->pseudo,
                'user_role' => $admin->role,
                'user_doc' => $admin->created_at,
                'action' => 'add',
                'table_concernee' => 'outils',
                'details' => "[SEEDER] Ajout de l'outil: {$outil->libelle} (Réf: {$outil->reference}, Qté: {$outil->quantite})",
                'created_at' => now(),
            ]);
        });
    }
}
