<?php

namespace App\Http\Resources\Customer;

use App\Http\Resources\Order\DeliveryOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravolt\Avatar\Facade as Avatar;

class CustomerOrderReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,

            'created_by' => $this->whenLoaded('user', $this->user?->name),
            'created_at' => $this->created_at->format('d-m-Y H:i:s'),
            'initial' => Avatar::create($this->name)->toBase64(),

            'orders' => DeliveryOrderResource::collection($this->whenLoaded('orders')),
            'summaries' => [
                'customer_price' => $this->orders->avg('customer_price'),
                'customer_total' => $this->orders->sum('customer_total'),
                'factory_price' => $this->orders->avg('net_price'),
                'margin' => $this->orders->avg('margin'),
                'gross_total' => $this->orders->sum('gross_total'),
                'total_weight' => $this->orders->sum('net_weight'),
                'ppn_total' => $this->orders->sum('ppn_total'),
                'pph22_total' => $this->orders->sum('pph22_total'),
                'gross_ppn_total' => $this->orders->sum('gross_total') + $this->orders->sum('ppn_total'),
                'total' => ($this->orders->sum('gross_total') + $this->orders->sum('ppn_total')) - $this->orders->sum('pph22_total'),
                'margin_income' => $this->orders->sum('net_total'),
            ]
        ];
    }
}
