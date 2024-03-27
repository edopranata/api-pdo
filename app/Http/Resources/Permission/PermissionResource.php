<?php

namespace App\Http\Resources\Permission;

use App\Http\Resources\Role\RoleResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $users = User::permission($this->name)->get();
        return [
            'id' => $this->id,
            'parent' => $this->parent,
            'children' => $this->children,
            'name' => $this->name,
            'title' => $this->title,
            'method' => $this->method,
            'path' => $this->path,
            'users' => $this->when($request->routeIs('app.management.permissions.viewPermission'), $users),
            'roles'  => RoleResource::collection($this->whenLoaded('roles')),
        ];
    }
}
