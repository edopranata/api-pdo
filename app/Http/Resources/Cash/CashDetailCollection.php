<?php

namespace App\Http\Resources\Cash;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CashDetailCollection extends ResourceCollection
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
            'data' => CashDetailResource::collection($this->collection->all()),
            'meta' => [
                'total' => $meta->has('total') ? (int)$meta->get('total') : 0,
            ],
        ];
    }
}
