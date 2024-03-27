<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data'  => UserResource::collection($this->collection->all())
        ];
    }

    public function with($request): array
    {
        if(!$request->routeIs('admin.management.users.index')) return [
            'roles' => Permission::query()->where('id', $request->id)->first()->roles()->whereNot('name', 'Administrator')->get()->pluck('name')
        ];
        $roles = collect([Role::query()->whereNot('name', 'Administrator')->get()->pluck('name')])->collapse();
        return [
            'roles' => $roles->all(),
        ];
    }
}
