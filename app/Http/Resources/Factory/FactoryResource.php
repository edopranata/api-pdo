<?php

namespace App\Http\Resources\Factory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FactoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'margin' => $this->margin,
            'ppn_tax' => $this->ppn_tax,
            'pph22_tax' => $this->pph22_tax,
            'created_by' => $this->whenLoaded('user', $this->user?->name),
            'created_at' => $this->created_at->format('d-m-Y H:i:s'),
            'prices' => FactoryPriceResource::collection($this->whenLoaded('prices')),
        ];
    }
}
