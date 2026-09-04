<?php

namespace Database\Seeders;

use App\Models\Mecanicien;
use App\Models\User;
use Illuminate\Database\Seeder;

class MecanicienSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupère un admin existant ou en crée un conforme à votre table
        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'admin']);

        // Génère 20 mécaniciens liés obligatoirement à ce admin
        Mecanicien::factory()
            ->count(20)
            ->create([
                'user_id' => $admin->id
            ]);
    }
}
