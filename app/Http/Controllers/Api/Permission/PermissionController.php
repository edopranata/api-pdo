<?php

namespace App\Http\Controllers\Api\Permission;

use App\Http\Controllers\Controller;
use App\Http\Resources\Permission\PermissionCollection;
use App\Http\Resources\Role\RoleCollection;
use App\Http\Resources\User\UserCollection;
use App\Models\Menu;
use App\Models\Spatie\Permission;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->parent) {
            $all = Permission::all();
            return $all->where('parent', $request->parent)->unique('children')->values()->map(function ($child) {
                return [
                    'id' => $child->id,
                    'label' => $child->children
                ];
            });
        } else if ($request->children) {
            $query = Permission::query()
                ->where('children', $request->children)
                ->when($request->get('sortBy'), function ($query, $sort) {
                    $sortBy = collect(json_decode($sort));
                    return $query->orderBy($sortBy['key'], $sortBy['order']);
                });

            return $request->get('limit', 0) > 0 ? $query->paginate($request->get('limit', 10)) : $query->get();
        } else {
            $all = Permission::all();
            $parent = $all->unique('parent')->values();
            return $parent->map(function ($first) use ($all) {
                return [
                    'id' => $first->id,
                    'label' => $first->parent,
                    'lazy' => true
                ];
            });
        }
    }

    public function view(string $id): PermissionCollection
    {
        $permission = Permission::query()
            ->with(['roles'])
            ->where('id', $id)->get();
        if ($permission->count() < 1) abort(404, 'Not found');
        return new PermissionCollection($permission);
    }

    public function sync(Request $request)
    {
        $permissions = collect(Route::getRoutes())
            ->whereNotNull('action.as')
            ->map(function ($route) {
                $action = collect($route->action)->toArray();
                $method = collect($route->methods)->first();
                $as = str($action['as'])->lower();
                $name = str($action['as'])->replace('admin.', '');
                return ($as->startsWith('admin') && !$as->endsWith('.')) ? [
                    'method' => $method,
                    'name' => $action['as'],
                    'parent' => \str(collect(\str($name)->explode('.'))[0])->headline(),
                    'children' => \str(collect(\str($name)->explode('.'))[1])->headline(),
                    'title' => \str(collect(\str($name)->explode('.'))[2])->headline(),
                    'path' => $route->uri
                ] : null;
            })
            ->filter(function ($value) {
                return !is_null($value);
            })->values();
        DB::beginTransaction();
        try {
            foreach ($permissions as $permission) {
                Permission::query()->firstOrCreate([
                    'name' => $permission['name']
                ], [
                    'guard_name' => 'web',
                    'parent' => $permission['parent'],
                    'children' => $permission['children'],
                    'title' => \str($permission['title'])->replace('app', '')->trim()->ucfirst(),
                    'path' => $permission['path'],
                    'method' => $permission['method'],
                ]);


                $path = collect(\str($permission['name'])->explode('.'));
                $menuParent = $path[1];
                $menuIndex = $path[2];

                $menu = Menu::query()
                    ->firstOrCreate([
                        'name' => $path[0] . '.' . $path[1],
                    ], [
                        'title' => \str($menuParent)->headline(),
                        'path' => \str($permission['path'])->replace('api', ''),
                        'icon' => 'double_arrow'
                    ]);
                if ($path->last() === 'index') {
                    $menu->children()->firstOrCreate([
                        'name' => $permission['name']
                    ], [
                        'title' => \str($menuIndex)->headline(),
                        'path' => \str($permission['path'])->replace('api', ''),
                    ]);
                }
            }

            $role = Role::query()->where('name', 'Administrator')->first();
            $role->syncPermissions(collect($permissions)->pluck('name'));

            DB::commit();
            return $this->list();


        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'error' => [
                    'code' => $exception->getCode(),
                    'massage' => $exception->getMessage()
                ]
            ], 301);
        }
    }

    private function list()
    {
        $all = Permission::all();
        $parent = $all->unique('parent')->values();
        return $parent->map(function ($first) use ($all) {
            return [
                'id' => $first->id,
                'label' => $first->parent,
                'lazy' => true
            ];
        });
    }

    public function viewRolesUsers(string $id, Request $request): UserCollection|RoleCollection
    {
        $type = $request->get('load');
        $permission = Permission::query()
            ->with(['roles'])
            ->where('id', $id)
            ->first();

        if (str($type)->lower() == 'users') {

            $query = User::permission($permission->name)->whereNotIn('id', [1, auth()->id()]);
            $query->when($request->get('name'), function ($query, $search) {
                return $query->where('name', 'LIKE', "%$search%");
            })->when($request->get('username'), function ($query, $search) {
                return $query->where('username', 'LIKE', "%$search%");
            })->when($request->get('email'), function ($query, $search) {
                return $query->where('email', 'LIKE', "%$search%");
            })->when($request->get('role'), function ($query, $search) {
                if ($search === 'All') {
                    return $query;
                } else {
                    return $query->whereHas('roles', function ($queryRole) use ($search) {
                        return $queryRole->where('name', $search);
                    });
                }
            })->when($request->get('sortBy'), function ($query, $sort) {
                $sortBy = collect(json_decode($sort));
                return $query->orderBy($sortBy['key'], $sortBy['order']);
            });
            $data = $query->paginate($request->get('limit', 10));

            return new UserCollection($data);
        } else {

            $query = $permission->roles();
            $query->when($request->get('sortBy'), function ($query, $sort) {
                $sortBy = collect(json_decode($sort));
                return $query->orderBy($sortBy['key'], $sortBy['order']);
            })->whereNot('id', 1);
            $data = $query->paginate($request->get('limit', 10));

            return new RoleCollection($data);
        }
    }
}
