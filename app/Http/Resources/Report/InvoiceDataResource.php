<?php

namespace App\Http\Resources\Report;

use App\Http\Resources\Customer\CustomerResource;
use App\Http\Resources\Loan\LoanResource;
use App\Http\Resources\Order\DeliveryOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $loan_installment = $this->whenLoaded('installment', $this->installment?->balance) ?? 0;
        $total_orders = $this->whenLoaded('orders', $this->orders->sum('customer_total'));
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'user_id' => $this->user_id,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->trade_date->format('d-m-Y'),
            'type' => $this->type,
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
            'orders' => DeliveryOrderResource::collection($this->whenLoaded('orders')),
            'installment' => LoanResource::make($this->whenLoaded('installment')),
            'loan_installment' => $loan_installment,
            'total_order' => $total_orders,
            'count_order' => $this->whenLoaded('orders', $this->orders->count()),
            'total' => $loan_installment + $total_orders
        ];
    }
}
