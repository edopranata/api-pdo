<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DeliveryOrderCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $meta = collect($this->resource);
        return [
            'data' => DeliveryOrderResource::collection($this->collection->all()),
            'meta' => [
                'total' => $meta->has('total') ? (int) $meta->get('total') : null,
            ],
            'orders' => [
                'gross_total' => $this->collection->sum('gross_total'),
                'total_weight' => $this->collection->sum('net_weight'),
                'ppn_total' => $this->collection->sum('ppn_total'),
                'pph22_total' => $this->collection->sum('pph22_total'),
                'gross_ppn_total' => $this->collection->sum('gross_total') + $this->collection->sum('ppn_total'),
                'total' => ($this->collection->sum('gross_total') + $this->collection->sum('ppn_total')) - $this->collection->sum('pph22_total'),
                'margin_income' => $this->collection->sum('net_total'),
            ]
        ];
    }
}
