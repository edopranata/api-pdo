<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Cash\CashDetailCollection;
use App\Http\Resources\Cash\CashDetailResource;
use App\Http\Resources\Cash\CashResource;
use App\Http\Resources\Menu\MenuResource;
use App\Http\Resources\Report\InvoiceDataResource;
use App\Models\Cash;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Laravolt\Avatar\Facade as Avatar;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($request->routeIs('admin.report.*') || $request->routeIs('admin.index') || $request->routeIs('admin.test') || $request->routeIs('admin.management.users.index') || $request->routeIs('admin.management.cash.index')) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'username' => $this->username,
                'email' => $this->email,
                'roles' => $this->getRoleNames(),
                'cash' => new CashResource($this->whenLoaded('cash')),
                'mutations' => CashDetailResource::collection($this->whenLoaded('mutations')),
                'invoice' => InvoiceDataResource::collection($this->whenLoaded('invoices')),
                'photo' => $this->photo ?? Avatar::create($this->name)->toBase64(),
                'created_at' => $this->created_at->format('d-m-Y H:i:s'),
            ];
        } else {
            $permissions = $this->getAllPermissions();

            return [
                'user' => [
                    'id' => $this->id,
                    'name' => $this->name,
                    'username' => $this->username,
                    'email' => $this->email,
                    'roles' => $this->getRoleNames(),
                    'photo_url' => $this->photo_url ?? Avatar::create($this->name)->toBase64(),
                ],
                'routes' => $permissions->pluck('name'),
                'menu' => MenuResource::collection($this->getMenu($permissions))

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
