<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravolt\Avatar\Facade as Avatar;

class CustomerResource extends JsonResource
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
            'loan' => $this->whenLoaded('loan', $this->loan?->balance),
            'created_by' => $this->whenLoaded('user', $this->user?->name),
            'created_at' => $this->created_at->format('d-m-Y H:i:s'),
            'initial' => Avatar::create($this->name)->toBase64(),
            'orders_count' => $this->orders_count,
            'total_weight' => $this->orders_sum_net_weight,
            'average_customer_price' => $this->orders_avg_customer_price,
            'customer_total' => $this->orders_sum_customer_total,
        ];
    }
}
