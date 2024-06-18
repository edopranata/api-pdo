<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Models\Customer;
use App\Models\Factory;
use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    public function index(Request $request)
    {

        $type = $request->get('type');
        switch ($type) {
            case "user":
                return $this->userCash();

            case "factories":
                return $this->factoryPrice();

            case "top_customer":
                return $this->topCustomer();

            case "annual_factory_chart":
                return $this->annualChart();

            default:
                return response()->json([
                    'status' => true,
                ], 201);

        }
    }

    private function userCash(): JsonResponse
    {
        $auth = auth('api')->user();
        $user = $auth->load('cash');
        return response()->json([
            'user' => UserResource::make($user),
        ]);
    }

    private function factoryPrice(): JsonResponse
    {
        $factories = Factory::query()->with(['prices' => function (Builder $builder) {
            $builder->whereDate('date', now());
        }])->get()->map(function ($factory) {
            $price = $factory->prices->map(function ($price) {
                return [
                    'id' => $price->id,
                    'date' => Carbon::create($price->date)->format('Y/m/d'),
                    'price' => $price->price,
                ];
            });
            return [
                'id' => $factory->id,
                'name' => $factory->name,
                'price' => collect($price)->first(),
            ];
        });

        return response()->json([
            'factories' => $factories,
        ]);
    }

    private function topCustomer(): JsonResponse
    {
        $date = now();
        $customer = Customer::query()
            ->whereHas('orders', function ($query) use ($date) {
                $query->whereMonth('trade_date', $date->format('m'))->whereYear('trade_date', $date->format('Y'));
            })
            ->withCount(['orders' => function ($query) use ($date) {
                $query->whereMonth('trade_date', $date->format('m'))->whereYear('trade_date', $date->format('Y'));
            }])
            ->withSum(['orders' => function ($query) use ($date) {
                $query->whereMonth('trade_date', $date->format('m'))->whereYear('trade_date', $date->format('Y'));
            }], 'net_weight')
            ->withAvg(['orders' => function ($query) use ($date) {
                $query->whereMonth('trade_date', $date->format('m'))->whereYear('trade_date', $date->format('Y'));
            }], 'customer_price')
            ->withSum(['orders' => function ($query) use ($date) {
                $query->whereMonth('trade_date', $date->format('m'))->whereYear('trade_date', $date->format('Y'));
            }], 'customer_total')
            ->orderByDesc('orders_sum_net_weight')
            ->take(10)->get()->map(function ($item) {
                return [
                    'name' => $item->name,
                    'orders_sum_net_weight' => number_format($item->orders_sum_net_weight / 1000, 1),
                ];
            });

        return response()->json([
            'best_customers' => [
                'options' => [
                    'chart' => [
                        'type' => 'bar',
                        'id' => 'top_customer',
                    ],
                    'xaxis' => [
                        'categories' => $customer->pluck('name')
                    ],
                    'dataLabels' => [
                        'enabled' => true,
                        'offsetX' => 0,
                        'style' => [
                            'color' => '#1976D2',
                        ]
                    ],

                    'title' => [
                        'text' => 'Top 10 Customer (ton) ' . $date->format('F Y'),
                        'align' => 'center',
                        'floating' => true
                    ],

                    'plotOptions' => [
                        'bar' => [
                            'borderRadius' => 4,
                            'borderRadiusApplication' => 'end',
                            'horizontal' => true,
                        ],
                    ]
                ],
                'series' => [
                    [
                        'name' => 'Tonase',
                        'data' => $customer->pluck('orders_sum_net_weight')
                    ]
                ],
            ]
        ], 201);
    }

    private function annualChart(): JsonResponse
    {
        $period = collect(CarbonPeriod::create(now()->startOfYear(), '1 month', now())->toArray())->map(function (Carbon $date) {
            return $date->format('Y-m');
        });

        $orders = Order::query()
            ->select(
                DB::raw('sum(net_weight) as sums'),
                DB::raw("DATE_FORMAT(trade_date,'%Y-%m') as months"),
                DB::raw("factory_id")
            )
            ->whereYear('trade_date', date('Y'))
            ->groupBy('months', 'factory_id')
            ->orderBy('months', 'ASC')
            ->get()->collect();

        $factories = Factory::all()->map(function ($item) use ($orders, $period) {
            return [
                'name' => $item->name,
                'data' => collect($period)->map(function ($date) use ($orders, $item) {
                    $sum = ($orders->where('factory_id', $item->id)->where('months', $date)->first()->sums ?? 0);
                    return $sum !== 0 ? number_format($sum / 1000, 1) : 0;
                })
            ];
        });

        return response()->json([
            'annual_factory_chart' => [
                'options' => [
                    'chart' => [
                        'type' => 'bar',
                        'id' => 'top_customer',
                    ],
                    'xaxis' => [
                        'categories' => $period
                    ],
                    'dataLabels' => [
                        'enabled' => true,
                        'offsetX' => 0,
                        'style' => [
                            'color' => '#1976D2',
                        ]
                    ],

                    'title' => [
                        'text' => 'Delivery Order Summaries ' .date('Y'),
                        'align' => 'center',
                        'floating' => true
                    ],

                    'plotOptions' => [
                        'bar' => [
                            'borderRadius' => 4,
                            'borderRadiusApplication' => 'end',
                            'horizontal' => false,
                            'columnWidth' => '55%',
                            'endingShape' => 'rounded'
                        ],
                    ]
                ],
                'series' => $factories
            ]
        ], 201);
    }
}
