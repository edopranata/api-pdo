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
//            'order' => [
//                'count' => $this->orders->count(),
//                'weight_total' => $this->orders->sum('net_weight'),
//                'customer_average_price' => $this->orders->avg('customer_price'),
//                'customer_total' => $this->orders->sum('customer_total'),
//            ],
            'orders' => [
                'count' => $this->orders->count(),
                'weight_total' => $this->orders->sum('net_weight'),
                'customer_average_price' => $this->orders->avg('customer_price'),
                'customer_total' => $this->orders->sum('customer_total'),
                'factory_price' => $this->orders->avg('net_price'),
                'margin' => $this->orders->avg('margin'),
                'gross_total' => $this->orders->sum('gross_total'),
                'ppn_total' => $this->orders->sum('ppn_total'),
                'pph22_total' => $this->orders->sum('pph22_total'),
                'gross_ppn_total' => $this->orders->sum('gross_total') + $this->orders->sum('ppn_total'),
                'total' => ($this->orders->sum('gross_total') + $this->orders->sum('ppn_total')) - $this->orders->sum('pph22_total'),
                'margin_income' => $this->orders->sum('net_total'),
                'net_income' => $this->orders->sum('net_total') - $this->orders->sum('pph22_total'),
            ],
            'order_details' => DeliveryOrderResource::collection($this->whenLoaded('orders'))
        ];
    }
}
