<?php

namespace App\Http\Resources\Loan;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
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
            'trade_date' => $this->trade_date,
            'opening_balance' => $this->opening_balance,
            'debit' => $this->balance > 0 ? $this->balance : 0,
            'credit' => $this->balance < 0 ? $this->balance : 0,
            'ending_balance' => $this->opening_balance + $this->balance
        ];
    }
}
