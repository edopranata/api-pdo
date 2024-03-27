<?php

namespace App\Http\Resources\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name'  => $this->name,
            'title' => $this->title,
            'path'  => $this->when($this->menu_id, $this->path),
            'icon'  => $this->when(!$this->menu_id, $this->icon),
            'children'  => MenuResource::collection($this->whenLoaded('children'))
        ];
    }
}
