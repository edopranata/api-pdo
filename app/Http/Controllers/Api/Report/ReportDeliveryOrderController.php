<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Http\Resources\Factory\FactoryCollection;
use App\Http\Resources\Factory\FactoryResource;
use App\Http\Resources\Order\DeliveryOrderCollection;
use App\Http\Resources\Order\DeliveryOrderResource;
use App\Http\Traits\OrderTrait;
use App\Models\Factory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportDeliveryOrderController extends Controller
{
    use OrderTrait;

    public function index(): JsonResponse
    {
        $factory = Factory::all();
        return response()->json([
            'factories' => FactoryResource::collection($factory),
        ], 201);
    }

    public function show(Factory $factory, Request $request)
    {
        $validator = Validator::make($request->only([
            'start_date', 'end_date'
        ]), [
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }else{
            $orders = $factory->order()
                ->with(['customer'])
                ->whereDate('trade_date', '>=', $request->get('start_date'))
                ->whereDate('trade_date', '<=', $request->get('end_date'))
                ->whereNull('income_status')
                ->get();
            return response()->json([
                'orders' => DeliveryOrderCollection::make($orders),
            ], 201);
        }
    }
}
