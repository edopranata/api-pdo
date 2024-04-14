<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CashSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::role('Cashier')->inRandomOrder()->limit(3)->get();
        foreach ($users as $user) {
            $balance = fake()->randomElement([500000000, 556000000, 686000000, 700000000, ]);
            $cash = $user->cash()->create([
                'balance' => $balance,
            ]);

            $cash->details()->create([
                'user_id' => $user->id,
                'trade_date' => now(),
                'opening_balance' => 0,
                'balance' => $balance,
            ]);
        }

    }
}
