<?php

namespace App\Http\Resources\Cash;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashResource extends JsonResource
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
            'balance' => $this->balance,
            'user' => new UserResource($this->whenLoaded('user')),
            'details' => CashDetailResource::collection($this->whenLoaded('details')),
        ];
    }
}
