<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mecanicien>
 */
class MecanicienFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::where('role', 'admin')->inRandomOrder()->first()?->id
                ?? User::factory()->create(['role' => 'admin'])->id,
            'nom' => $this->faker->unique()->lastName(), // unique() évite de bloquer sur votre vérification
            'prenom' => $this->faker->unique()->firstName(),
            'type' => $this->faker->randomElement(['interne', 'externe']),
            'vehicules_maitrises' => $this->faker->randomElement(['Toyota, Peugeot, Hyundai', 'Yamaha, Honda', 'Mercedes Actros, Volvo FMX']),
            'experience' => $this->faker->numberBetween(1, 10),
            'contact' => $this->faker->phoneNumber(),
            'contact_urgence' => $this->faker->phoneNumber(),
        ];
    }
}
