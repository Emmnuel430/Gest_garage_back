<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vehicule;
use App\Models\Reception;
use App\Models\User;
use App\Models\Mecanicien;

class ReceptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. On s'assure d'avoir un gardien actif pour enregistrer cet afflux
        $gardien = User::where('role', 'gardien')->first()
            ?? User::factory()->create(['role' => 'gardien']);

        // 2. On s'assure d'avoir des mécaniciens disponibles en BDD
        if (Mecanicien::count() === 0) {
            Mecanicien::factory()->count(5)->create();
        }

        // 3. Configuration de la simulation (ex: 25 véhicules arrivent aujourd'hui)
        $nombreVehiculesDuJour = 25;

        for ($i = 0; $i < $nombreVehiculesDuJour; $i++) {

            // On crée un véhicule totalement unique
            $vehicule = Vehicule::factory()->create();

            // On crée IMMÉDIATEMENT sa réception unique pour aujourd'hui
            // L'Observer intercepte ce code et génère automatiquement le PDF d'entrée et le Log
            Reception::factory()->create([
                'vehicule_id' => $vehicule->id,
                'created_by_id' => $gardien->id,
                'date_arrivee' => now(), // Enregistré à l'instant présent
                'statut' => 'attente',   // Strictement en attente de validation
            ]);
        }
    }
}
