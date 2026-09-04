<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Outil>
 */
class OutilFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $outilsMecanique = [
            'Clé à cliquet',
            'Jeu de tournevis',
            'Cric hydraulique',
            'Clef dynamométrique',
            'Pince multiprise',
            'Chariot de visite',
            'Compresseur d\'air',
            'Pistolet pneumatique',
            'Boîte à outils complète',
            'Multimètre digital',
            'Pont élévateur mobile',
            'Équilibreuse de roue',
            'Extracteur de rotule',
            'Lampe d\'inspection LED',
            'Jeu de clés Allen'
        ];

        return [
            // Utilise un outil aléatoire ou en génère un au hasard si la liste est dépassée
            'libelle' => $this->faker->randomElement($outilsMecanique) . ' ' . $this->faker->numberBetween(1, 3),
            'quantite' => $this->faker->numberBetween(0, 50),
            // Génère une référence unique structurée (ex: REF-74291)
            'reference' => 'REF-' . $this->faker->unique()->numberBetween(10000, 99999),
        ];
    }
}
