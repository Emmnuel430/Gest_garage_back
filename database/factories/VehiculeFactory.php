<?php

namespace Database\Factories;

use App\Models\Mecanicien;
use App\Models\Vehicule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicule>
 */
class VehiculeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Vehicule::class;

    public function definition(): array
    {
        $marques = ['Toyota', 'Peugeot', 'Hyundai', 'Mercedes', 'Ford', 'Nissan', 'BMW', 'Renault', 'Volkswagen', 'Honda'];
        $modeles = ['Corolla', '208', 'Tucson', 'C-Class', 'Focus', 'Navara', '3 Series', 'Clio', 'Golf', 'Civic'];

        return [
            // Format d'immatriculation fictif (ex: AA-123-BB ou 1234-AB-01)
            'immatriculation' => strtoupper($this->faker->unique()->bothify('??-###-??')),
            'marque' => $this->faker->randomElement($marques),
            'modele' => $this->faker->randomElement($modeles),
            // Lie à un mécanicien existant ou en crée un via sa factory
            'mecanicien_id' => Mecanicien::inRandomOrder()->first()?->id ?? Mecanicien::factory(),
            'fiche_entree_vehicule' => null, // Rempli automatiquement par l'Observer lors de la réception
        ];
    }
}
