<?php

namespace App\Http\Resources\Loan;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LoanDetailCollection extends ResourceCollection
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
            'data' => LoanDetailResource::collection($this->collection->all()),
            'meta' => [
                'total' => $meta->has('total') ? (int)$meta->get('total') : 0,
            ],
        ];
    }
}
