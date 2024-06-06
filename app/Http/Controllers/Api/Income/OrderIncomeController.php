<?php

namespace App\Http\Controllers\Api\Income;

use App\Http\Controllers\Controller;
use App\Http\Resources\Factory\FactoryResource;
use App\Http\Resources\Order\DeliveryOrderCollection;
use App\Models\Factory;
use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderIncomeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->get('factory_id') && $request->get('period_start') && $request->get('period_end')) {
            $query = Order::query()
                ->with(['factory', 'customer'])
                ->whereNull('income_status')
                ->when($request->get('factory_id'), function ($query, $factoryId) {
                    $query->where('factory_id', $factoryId);
                })
                ->when($request->get('period_start'), function ($builder, $periodStart) {
                    $builder->whereDate('trade_date', '>=', $periodStart);
                })
                ->when($request->get('period_end'), function ($builder, $periodEnd) {
                    $builder->whereDate('trade_date', '<=', $periodEnd);
                })
                ->get();

            return response()->json([
                'order' => DeliveryOrderCollection::make($query),
            ], 201);
        } else {
            $factories = Factory::query()
                ->with(['order' => function ($query) {
                    return $query->whereNull('income_status');
                }])

                ->get()->map(function ($factory) {
                    return [
                        'id' => $factory->id,
                        'name' => $factory->name,
                        'order' => [
                            'min' => $factory->order->count() > 0 ? $factory->order->min('trade_date')->format('Y/m/d') : null,
                            'max' => $factory->order->count() > 0 ? $factory->order->max('trade_date')->format('Y/m/d') : null,
                            'period' => $factory->order->count() > 0 ? collect(CarbonPeriod::create($factory->order->min('trade_date'), $factory->order->max('trade_date'))->toArray())->map(function ($date) {
                                return Carbon::parse($date)->format('Y/m/d');
                            }) : null,
                            'income_period' => $factory->order->count() > 0 ? collect(CarbonPeriod::create($factory->order->min('trade_date'), now())->toArray())->map(function ($date) {
                                return Carbon::parse($date)->format('Y/m/d');
                            }) : null,
                        ],
                    ];
                });
            return response()->json([
                'factories' => $factories,
            ], 201);
        }

    }

    public function store(Factory $factory, Request $request)
    {
        $validator = Validator::make($request->only([
            'trade_date', 'period_start', 'period_end'
        ]), [
            'trade_date' => 'required|date|before_or_equal:' . Carbon::now()->toDateString(),
            'period_start' => 'required|date|before_or_equal:period_end',
            'period_end' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }

        $orders = $factory->order()
            ->whereNull('income_status')
            ->whereDate('trade_date', '>=', $request->get('period_start'))
            ->whereDate('trade_date', '<=', $request->get('period_end'));
        DB::beginTransaction();
        try {
            if ($orders->exists()) {

                $income = $factory->incomes()
                    ->create([
                        'period_start' => $request->get('period_start'),
                        'period_end' => $request->get('period_end'),
                        'trade_date' => $request->get('trade_date'),
                        'user_id' => auth('api')->id()
                    ]);

                foreach ($orders->get() as $order) {
                    $order->update([
                        'income_status' => $income->trade_date
                    ]);

                    $income->details()->create([
                        'order_id' => $order->id,
                    ]);
                }
                DB::commit();
                return response()->json(['status' => true], 201);

            } else {
                return response()->json(['status' => false, 'errors' => ['factory_id' => ['Factory in this period is no data']]], 422);
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }
}
