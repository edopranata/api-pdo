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
    $period = CarbonPeriod::create(now(), '1 month', now()->addMonth());

    if($period->count() > 0){

        $orders = Order::query()
            ->select(
                DB::raw('sum(net_weight) as sums'),
                DB::raw("DATE_FORMAT(trade_date,'%m %Y') as months"),
                DB::raw("factory_id")
            )
            ->whereDate('trade_date', '>=', $period->first()->startOfMonth()->format('Y-m-d'))
            ->whereDate('trade_date', '<=', $period->last()->endOfMonth()->format('Y-m-d'))
            ->groupBy('months', 'factory_id')
            ->orderBy('months', 'ASC')
            ->get()->collect();
    }



    return response()->json([
        'period' => $period,
        'orders' => $orders ?? null,
        'first' => $period->first()->startOfMonth(),
        'last' => $period->last()->endOfMonth(),
    ]);
//

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
