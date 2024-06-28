<?php

namespace App\Http\Resources\Income;

use App\Http\Resources\Factory\FactoryResource;
use App\Http\Resources\Order\DeliveryOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncomeDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'factory' => new FactoryResource($this->whenLoaded('factory')),
            'trade_date' => $this->trade_date->format('Y-m-d'),
            'period_start' => $this->period_start->format('Y-m-d'),
            'period_end' => $this->period_end->format('Y-m-d'),
            'orders' => DeliveryOrderResource::collection($this->whenLoaded('orders')),
            'summaries' => $this->whenLoaded('orders', [
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
                'net_income' => $this->orders->sum('gross_total')  - $this->orders->sum('pph22_total') - $this->orders->sum('customer_total')
            ]),
        ];
    }
}
