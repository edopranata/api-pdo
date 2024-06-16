<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'Backend Version' => App::version(),
        'Environment' => App::environment()
    ], 201);
});

Route::get('/test', function (){
    \App\Models\Invoice::query()
        ->withTrashed()
        ->get()->chunk(100)->each(function ($items) {
            foreach ($items as $item) {
                $item->update([
                    'trade_date' => $item->created_at
                ]);
            }
        });

    \App\Models\CashDetail::query()
        ->withTrashed()
        ->get()->chunk(100)->each(function ($items) {
            foreach ($items as $item) {
                $item->update([
                    'trade_date' => $item->created_at
                ]);
            }
        });

    \App\Models\LoanDetail::query()
        ->withTrashed()
        ->get()->chunk(100)->each(function ($items) {
            foreach ($items as $item) {
                $item->update([
                    'trade_date' => $item->created_at
                ]);
            }
        });
});
