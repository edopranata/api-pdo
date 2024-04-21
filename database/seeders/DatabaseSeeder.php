<?php

namespace Database\Seeders;

use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $administrator = 'Administrator';

        $user = User::factory()->create([
            'name' => $administrator,
            'username' => \str($administrator)->lower(),
            'email' => \str($administrator)->lower().'@admin.com',
        ]);

        $roles = [
            'Admin', 'Cashier'
        ];

        Role::create(['name' => $administrator]);
        foreach ($roles as $role) {
            Role::create(['name' => $role]);

//            if($role === 'Cashier') {
//                User::factory(rand(10, 20))->create()->each(function ($user) use ($role) {
//                    $user->assignRole($role);
//                });
//
//            }else{
                $admin = User::factory()->create([
                    'name' => $role,
                    'username' => \str($role)->lower(),
                    'email' => \str($role)->lower().'@admin.com',
                ]);

                $admin->assignRole($role);
//            }



        }

        $permissions = collect(Route::getRoutes())
            ->whereNotNull('action.as')
            ->map(function ($route) {
                $action = collect($route->action)->toArray();
                $method = collect($route->methods)->first();
                $as = str($action['as'])->lower();
                if ($as->startsWith('admin') && !$as->endsWith('.')) {
                    $name = Str::replace('admin.', '', $action['as']);
                    return [
                        'method' => $method,
                        'name' => $action['as'],
                        'parent' => \str(collect(\str($name)->explode('.'))[0])->headline(),
                        'children' => \str(collect(\str($name)->explode('.'))[1])->headline(),
                        'title' => \str(collect(\str($name)->explode('.'))[2])->headline(),
                        'path' => $route->uri
                    ];
                }else {
                    return null;
                }
            })
            ->filter(function ($value) {
                return !is_null($value);
            });

        $role = Role::query()->first();
        foreach ($permissions as $item) {
            $permission = Permission::create([
                'name'  => $item['name'],
                'parent' => $item['parent'],
                'children' => $item['children'],
                'title' => \str($item['title'])->replace('app', '')->trim()->ucfirst(),
                'path'  => $item['path'],
                'method'  => $item['method'],
            ]);

            if(\str($permission->title)->lower() == 'index'){
                $other_roles  = Role::query()->whereNotIn('id', [1])->inRandomOrder()->first();
                $other_roles->givePermissionTo($permission->name);
            }
        }

        $role->syncPermissions(collect($permissions)->pluck('name'));

        $user->assignRole($administrator);

        $this->call([
            MenuSeeder::class,
            //CustomerSeeder::class,
            FactorySeeder::class,
            //CashSeeder::class
        ]);
    }
}
