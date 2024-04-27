<?php

namespace Database\Seeders;

use App\Models\Factory;
use App\Models\User;
use Carbon\CarbonPeriod;
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
                'ppn_tax' => 11,
                'pph22_tax' => 0.25,
            ])->each(function (Factory $factory) {
                $now = now();
                $periods = CarbonPeriod::create($now->subDays(rand(20, 100)), now()->subdays(rand(2, 5)));

                foreach ($periods as $period) {
                    $factory->prices()
                        ->create([
                            'date' => $period,
                            'price' => (int) fake()->randomElement([2100, 2200, 2300, 2400]) + (int) fake()->randomElement([5,10,15,20.25,30,35,40,45,50]),
                        ]);
                }
            });
    }
}
