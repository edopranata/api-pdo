<?php

namespace App\Http\Controllers\Api\Income;

use App\Http\Controllers\Controller;
use App\Http\Resources\Factory\FactoryResource;
use App\Http\Resources\Order\DeliveryOrderCollection;
use App\Models\Factory;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class OrderIncomeController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()
            ->with(['factory'])
            ->when($request->get('factory_id'), function ($query, $factoryId) {
                $query->where('factory_id', $factoryId);
            })
            ->when($request->get('period_start'), function ($builder, $periodStart) {
                $builder->whereDate('trade_date', '>=', $periodStart);
            })
            ->when($request->get('period_end'), function ($builder, $periodEnd) {
                $builder->whereDate('trade_date', '<=', $periodEnd);
            })
            ->when($request->get('sortBy'), function ($query, $sort) {
                $sortBy = collect(json_decode($sort));
                return $query->orderBy($sortBy['key'], $sortBy['order']);
            })
            ->when(!$request->get('sortBy'), function ($query) {
                return $query->orderByDesc('id');
            })->get();


        $factories = Factory::query()->get();
        return response()->json([
            'order' => DeliveryOrderCollection::make($query),
            'factories' => FactoryResource::collection($factories),
        ], 201);
    }

    public function store()
    {

    }
}
