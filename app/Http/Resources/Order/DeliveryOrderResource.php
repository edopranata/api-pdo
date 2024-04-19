<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\Customer\CustomerResource;
use App\Http\Resources\Factory\FactoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOrderResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'factory_id' => $this->factory_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'factory' => new FactoryResource($this->whenLoaded('factory')),
            'created_by' => $this->whenLoaded('user', $this->user?->name),
            'trade_date' => $this->whenNotNull($this->trade_date->format('Y/m/d H:i:s')),

            'net_weight' => $this->net_weight,
            'net_price' => $this->net_price,
            'margin' => $this->margin,
            'ppn_tax' => $this->ppn_tax,
            'pph22_tax' => $this->pph22_tax,
            'ppn_total' => $this->ppn_total,
            'pph22_total' => $this->pph22_total,
            'gross_total' => $this->gross_total,
            'net_total' => $this->net_total,
            'customer_price' => $this->customer_price,
            'customer_total' => $this->customer_total,

            'invoice_status' => $this->whenNotNull($this->invoice_status ? $this->invoice_status->format('Y/m/d H:i:s') : null),
            'income_status' => $this->whenNotNull($this->income_status ? $this->income_status->format('Y/m/d H:i:s') : null),
            'created_at' => $this->whenNotNull($this->created_at->format('Y/m/d H:i:s')),
        ];
    }
}
