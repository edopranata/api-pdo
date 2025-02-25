<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseUploadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        User::query()->each(function (User $user) {
            $user->update([
                'password' => Hash::make('Pr123588+-')
            ]);
        });

        Customer::query()->each(function (Customer $customer) {
            $customer->update([
                'name'  => fake('id_ID')->name,
                'phone'  => fake('id_ID')->phoneNumber,
                'address'  => fake('id_ID')->address
            ]);
        });

        Factory::query()->each(function (Factory $factory) {
            $factory->update([
                'name'  => fake('id_ID')->company,
            ]);
        });

    }
}
