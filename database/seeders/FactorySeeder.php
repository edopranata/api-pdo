<?php

namespace Database\Seeders;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FactorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Factory::query()
            ->create([
                'user_id' => User::query()->first()->id,
                'name' => 'PT. Tasma Puja',
                'margin' => 35,
                'price' => 2300,
                'ppn_tax' => 11,
                'pph22_tax' => 0.25,
            ]);
    }
}
