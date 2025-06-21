<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CheckItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            ['nom' => 'essuie glace', 'type' => 'etat'],
            ['nom' => 'vitres avant', 'type' => 'etat'],
            ['nom' => 'vitres arrière', 'type' => 'etat'],
            ['nom' => 'phares avant', 'type' => 'etat'],
            ['nom' => 'phares arrière', 'type' => 'etat'],
            ['nom' => 'pneus secours', 'type' => 'presence'],
            ['nom' => 'cric', 'type' => 'presence'],
            ['nom' => 'peinture', 'type' => 'etat'],
            ['nom' => 'retroviseur', 'type' => 'etat'],
            ['nom' => 'kit pharmacie', 'type' => 'presence'],
            ['nom' => 'triangle', 'type' => 'presence'],
        ];

        foreach ($items as $item) {
            \App\Models\CheckItem::create($item);
        }

    }
}
