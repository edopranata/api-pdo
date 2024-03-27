<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Menu\MenuResource;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if($request->routeIs('admin.management.users.index')){
            return [
                'id'        => $this->id,
                'name'      => $this->name,
                'username'  => $this->username,
                'email'     => $this->email,
                'roles'     => $this->getRoleNames(),
                'created_at'=> $this->created_at->format('d-m-Y H:i:s'),
            ];
        }else{
            $permissions = $this->getAllPermissions();

            return [
                'user'      => [
                    'id'            => $this->id,
                    'name'          => $this->name,
                    'username'      => $this->username,
                    'email'         => $this->email,
                    'roles'     => $this->getRoleNames(),
                    'photo_url'     => $this->photo_url ?? asset('/avatar.svg'),
                ],
                'routes'        => $permissions->pluck('name'),
                'menu'          => MenuResource::collection($this->getMenu($permissions))

            ];
        }
    }

    private function getMenu($permissions): Collection|array
    {
        return Menu::query()
            ->whereNull('menu_id')
            ->with('children', function ($menu) use ($permissions) {
                $menu->whereIn('name', $permissions->pluck('name'));
            })
            ->get();
    }
}
