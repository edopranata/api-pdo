<?php

namespace App\Http\Resources\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'  => $this->id,
            'name'  => $this->name,
            'users_count' => $this->when($request->routeIs('app.management.roles.index'), $this->users->count()),
            'permissions_count' => $this->when($request->routeIs('app.management.roles.index'), $this->permissions->count()),
            'created_at'  => $this->when($this->created_at, $this->created_at->format('d-m-Y H:i:s')),
        ];
    }
}
