<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('fr_FR');

        // Créer un super admin en premier
        User::create([
            'first_name' => $faker->firstName(),
            'last_name' => $faker->lastName(),
            'pseudo' => $faker->unique()->userName(),
            'password' => bcrypt('12345'),
            'role' => 'super_admin',
        ]);

        foreach (range(1, 9) as $index) {
            User::create([
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'pseudo' => $faker->unique()->userName(),
                'password' => bcrypt('12345'),
                'role' => $faker->randomElement(['super_admin', 'gardien', 'secretaire', 'chef_atelier', 'caisse']),
            ]);
        }
    }
}
