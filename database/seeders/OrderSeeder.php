<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Factory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

            $factory = Factory::query()->with('prices')->inRandomOrder()->first();

            foreach ($factory->prices as $price) {
                $margin = fake()->randomElement([35,40,45,50]);
                $customers = Customer::query()->inRandomOrder()->take(rand(20,50))->get();
                foreach ($customers as $customer) {
                    $net_weight = fake()->randomElement([8000, 9000, 10000, 11000]) + (fake()->randomElement([5,10,15,20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95]) * rand(1,5));

                    $factory_id = $factory->id;
                    $trade_date = $price->date;
                    $ppn_tax = $factory->ppn_tax;
                    $pph22_tax = $factory->pph22_tax;
                    $net_price = $price->price;
                    $gross_total = $net_price * $net_weight;
                    $net_total = $net_weight * $margin;
                    $customer_price = $net_price - $margin;
                    $customer_total = $customer_price * $net_weight;

                    $ppn_total = ($gross_total * $ppn_tax) / 100;
                    $pph22_total = ($gross_total * $pph22_tax) / 100;

                    $customer->orders()->create([
                        'trade_date' => $trade_date,
                        'factory_id' => $factory_id,
                        'net_weight' => $net_weight,
                        'net_price' => $net_price,
                        'gross_total' => $gross_total,
                        'net_total' => $net_total,
                        'customer_price' => $customer_price,
                        'customer_total' => $customer_total,
                        'margin' => $margin,
                        'ppn_tax' => $ppn_tax,
                        'pph22_tax' => $pph22_tax,
                        'ppn_total' => $ppn_total,
                        'pph22_total' => $pph22_total,
                        'user_id' => User::query()->first()->id,
                    ]);
                }
            }

    }
}
