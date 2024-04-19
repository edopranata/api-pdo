<?php

namespace App\Http\Resources\Customer;

use App\Http\Resources\Order\DeliveryOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravolt\Avatar\Facade as Avatar;

class CustomerOrderResource extends JsonResource
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
            'phone' => $this->phone,
            'address' => $this->address,
            'initial' => Avatar::create($this->name)->toBase64(),
            'loan' => $this->whenLoaded('loan', $this->loan?->balance),
            'order' => [
                'count' => $this->orders->count(),
                'weight_total' => $this->orders->sum('net_weight'),
                'customer_average_price' => $this->orders->avg('customer_price'),
                'customer_total' => $this->orders->sum('customer_total'),
            ],
            'order_details' => DeliveryOrderResource::collection($this->whenLoaded('orders'))
        ];
    }
}
