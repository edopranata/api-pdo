<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data'  => CustomerResource::collection($this->collection->all()),
            'summaries' => [
                'loan' => $this->collection->sum('loan.balance'),
                'orders_count' => $this->collection->sum('orders_count'),
                'total_weight' => $this->collection->sum('orders_sum_net_weight'),
                'average_customer_price' => $this->collection->average('orders_avg_customer_price'),
                'customer_total' => $this->collection->sum('orders_sum_customer_total')
            ]
        ];
    }
}
