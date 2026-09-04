<?php

namespace Database\Factories;

use App\Models\Reception;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reception>
 */
class ReceptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Reception::class;

    public function definition(): array
    {
        $motifs = ['Panne moteur', 'Révision périodique', 'Problème de freins', 'Changement d\'embrayage', 'Climatisation en panne', 'Problème électrique', 'Réparation de carrosserie', 'Vidange et entretien', 'Problème de suspension', 'Diagnostic électronique'];

        return [
            'vehicule_id' => Vehicule::factory(), // Sera surchargé dans le seeder
            // Récupère un utilisateur avec le rôle 'gardien' ou en crée un
            'created_by_id' => User::where('role', 'gardien')->inRandomOrder()->first()?->id
                ?? User::factory()->create(['role' => 'gardien'])->id,
            'motif_visite' => $this->faker->randomElement($motifs),
            'date_arrivee' => $this->faker->dateTimeBetween('-5 days', 'now'),
            'statut' => 'attente', // Statut initial par défaut
        ];
    }
}
