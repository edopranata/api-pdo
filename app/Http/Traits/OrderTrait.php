<?php

namespace App\Http\Traits;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait OrderTrait
{

    private function getOrderTable(Request $request): LengthAwarePaginator
    {
        $query = Order::query()
            ->with(['customer', 'factory', 'user'])
            ->when($request->get('customer_id'), function ($query, $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->when($request->get('factory_id'), function ($query, $factoryId) {
                $query->where('factory_id', $factoryId);
            })
            ->when($request->get('sortBy'), function ($query, $sort) {
                $sortBy = collect(json_decode($sort));
                return $query->orderBy($sortBy['key'], $sortBy['order']);
            })
            ->when(!$request->get('sortBy'), function ($query) {
                return $query->orderByDesc('id');
            });

        return $query->paginate($request->get('limit', 10));
    }
}
