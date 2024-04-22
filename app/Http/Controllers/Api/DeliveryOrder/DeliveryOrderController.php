<?php

namespace App\Http\Controllers\Api\DeliveryOrder;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerResource;
use App\Http\Resources\Factory\FactoryResource;
use App\Http\Resources\Order\DeliveryOrderCollection;
use App\Http\Resources\Order\DeliveryOrderResource;
use App\Http\Traits\OrderTrait;
use App\Models\Customer;
use App\Models\Factory;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DeliveryOrderController extends Controller
{
    use OrderTrait;
    public function index(Request $request): JsonResponse
    {

        $deliveries = $this->getOrderTable($request);

        $customers = Customer::query()->with('loan')->get();
        $factories = Factory::query()->get();
        return response()->json([
            'order' => DeliveryOrderCollection::make($deliveries),
            'customers' => CustomerResource::collection($customers),
            'factories' => FactoryResource::collection($factories)
        ], 201);
    }

    public function store(Factory $factory, Request $request)
    {
        $now = Carbon::now();
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->only([
                'trade_date', 'customer_id', 'net_weight', 'net_price', 'margin', 'ppn_tax', 'pph22_tax'
            ]), [
                'trade_date' => 'required|date|before_or_equal:' . Carbon::now()->toDateString(),
                'customer_id' => 'required|exists:customers,id',
                'net_weight' => 'required|numeric|min:1',
                'net_price' => 'required|numeric|min:1',
                'margin' => 'required|numeric|min:1|max:100',
                'ppn_tax' => 'required|numeric|min:1|max:100',
                'pph22_tax' => 'required|numeric|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
            }

            $customer_price = $request->get('net_price') - $request->get('margin');
            $customer_total = $customer_price * $request->get('net_weight');
            $delivery = $factory->order()
                ->create([
                    'trade_date' => Carbon::createFromFormat('Y/m/d H:i:s', $request->get('trade_date') . ' ' . $now->format('H:i:s')),
                    'customer_id' => $request->get('customer_id'),
                    'net_weight' => $request->get('net_weight'),
                    'net_price' => $request->get('net_price'),
                    'gross_total' => $request->get('gross_total'),
                    'net_total' => $request->get('net_total'),
                    'customer_price' => $customer_price,
                    'customer_total' => $customer_total,
                    'margin' => $request->get('margin'),
                    'ppn_tax' => $request->get('ppn_tax'),
                    'pph22_tax' => $request->get('pph22_tax'),
                    'ppn_total' => $request->get('ppn'),
                    'pph22_total' => $request->get('pph22'),
                    'user_id' => auth('api')->id()
                ]);

            DB::commit();
            return new DeliveryOrderResource($delivery->load(['user', 'customer', 'factory']));

        } catch (\Exception $exception) {
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {

        $now = Carbon::now();
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->only([
                'trade_date', 'customer_id', 'factory_id', 'net_weight', 'net_price', 'margin', 'ppn_tax', 'pph22_tax',
            ]), [
                'trade_date' => 'required|date|before_or_equal:' . Carbon::now()->toDateString(),
                'factory_id' => 'required|exists:factories,id',
                'customer_id' => 'required|exists:customers,id',
                'net_weight' => 'required|numeric|min:1',
                'net_price' => 'required|numeric|min:1',
                'margin' => 'required|numeric|min:1|max:100',
                'ppn_tax' => 'required|numeric|min:1|max:100',
                'pph22_tax' => 'required|numeric|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
            }

            $customer_price = $request->get('net_price') - $request->get('margin');
            $customer_total = $customer_price * $request->get('net_weight');

            $order->update([
                'trade_date' => Carbon::createFromFormat('Y/m/d H:i:s', $request->get('trade_date') . ' ' . $now->format('H:i:s')),
                'customer_id' => $request->get('customer_id'),
                'factory_id' => $request->get('factory_id'),
                'net_weight' => $request->get('net_weight'),
                'net_price' => $request->get('net_price'),
                'gross_total' => $request->get('gross_total'),
                'net_total' => $request->get('net_total'),
                'customer_price' => $customer_price,
                'customer_total' => $customer_total,
                'margin' => $request->get('margin'),
                'ppn_tax' => $request->get('ppn_tax'),
                'pph22_tax' => $request->get('pph22_tax'),
                'ppn_total' => $request->get('ppn'),
                'pph22_total' => $request->get('pph22'),
            ]);

            DB::commit();

            return response()->json(['status' => true], 201);

        } catch (\Exception $exception) {
            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        DB::beginTransaction();
        try {

            $order->delete();

            DB::commit();
            return response()->json(['status' => true], 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'error' => [
                    'code' => $exception->getCode(),
                    'massage' => $exception->getMessage()
                ]
            ], 301);
        }
    }
}
