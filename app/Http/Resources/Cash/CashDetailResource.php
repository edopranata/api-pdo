<?php

namespace App\Http\Resources\Cash;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashDetailResource extends JsonResource
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
            'description' => $this->description,
            'trade_date' => $this->created_at->format('Y-m-d H:i:s'),
            'opening_balance' => $this->opening_balance,
            'balance_in' => $this->balance > 0 ? $this->balance : null,
            'balance_out' => $this->balance < 0 ? $this->balance * -1 : null,
            'ending_balance' => $this->opening_balance + $this->balance
        ];
    }
}
