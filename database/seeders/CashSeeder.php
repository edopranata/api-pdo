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
            $balance = fake()->randomElement([5000000000, 5560000000, 6860000000, 7000000000, ]);
            $cash = $user->cash()->create([
                'balance' => $balance,
            ]);

            $cash->details()->create([
                'user_id' => $user->id,
                'trade_date' => now(),
                'description' => 'Kas pagi',
                'opening_balance' => 0,
                'balance' => $balance,
            ]);
        }

    }
}
