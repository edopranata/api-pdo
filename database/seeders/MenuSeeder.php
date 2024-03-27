<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = Permission::all();

        foreach ($permissions as $key => $permission) {
            $params = collect(\str($permission['name'])->explode('.'));
            $menuParent = $params[1];
            $menuIndex = $params[2];

            $menu = Menu::query()
                ->firstOrCreate([
                    'name' => $params[0] . '.' . $params[1],
                ], [
                    'title' => \str($menuParent)->headline(),
                    'path' => \str($permission['path'])->replace('api', ''),
                    'icon' => 'double_arrow'
                ]);
            if ($params->last() === 'index') {
                $menu->children()->firstOrCreate([
                    'name' => $permission['name']
                ], [
                    'title' => \str($menuIndex)->headline(),
                    'path' => \str($permission['path'])->replace('api', ''),
                ]);
            }
        }
    }
}
