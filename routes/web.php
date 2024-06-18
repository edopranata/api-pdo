<?php

use App\Models\Factory;
use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'Backend Version' => App::version(),
        'Environment' => App::environment()
    ], 201);
});

Route::get('/test', function () {
//    $period = collect(CarbonPeriod::create(now()->startOfYear(), '1 month', now())->toArray())->map(function (Carbon $date) {
//        return $date->format('m Y');
//    });
//
//    $orders = Order::query()
//        ->select(
//            DB::raw('sum(net_weight) as sums'),
//            DB::raw("DATE_FORMAT(trade_date,'%m %Y') as months"),
//            DB::raw("factory_id")
//        )
//        ->whereYear('trade_date', date('Y'))
//        ->groupBy('months', 'factory_id')
//        ->orderBy('months', 'ASC')
//        ->get()->collect();
//
//    $factories = Factory::all()->map(function ($item) use ($orders, $period) {
//        return [
//            'name' => $item->name,
//            'data' => collect($period)->map(function ($date) use ($orders, $item) {
//                return $orders->where('factory_id', $item->id)->where('months', $date)->first()->sums ?? 0;
//            })
//        ];
//    });
//
//    return response()->json([
//        'best_customers' => [
//            'options' => [
//                'chart' => [
//                    'type' => 'bar',
//                    'id' => 'top_customer',
//                ],
//                'xaxis' => [
//                    'categories' => $period //
//                ],
//                'dataLabels' => [
//                    'enabled' => true,
//                    'offsetX' => 0,
//                    'style' => [
//                        'color' => '#1976D2',
//                    ]
//                ],
//
//                'title' => [
//                    'text' => 'Top 10 Customer (ton) ',
//                    'align' => 'center',
//                    'floating' => true
//                ],
//
//                'plotOptions' => [
//                    'bar' => [
//                        'borderRadius' => 4,
//                        'borderRadiusApplication' => 'end',
//                        'horizontal' => false,
//                        'columnWidth' => '55%',
//                        'endingShape' => 'rounded'
//                    ],
//                ]
//            ],
//            'series' => $factories
//        ]
//    ], 201);
});
