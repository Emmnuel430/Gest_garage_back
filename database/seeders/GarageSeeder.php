<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Garage;

use Faker\Factory as Faker;

class GarageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('fr_FR');
        for ($i = 1; $i <= 10; $i++) {
            Garage::create([
                'nom' => 'Garage ' . $faker->lastName(),
                'adresse' => 'Rue numéro ' . rand(1, 100) . ', Quartier ' . chr(65 + $i),
            ]);
        }
    }
}
