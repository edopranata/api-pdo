<?php

use App\Http\Resources\Customer\CustomerCollection;
use App\Http\Resources\Customer\CustomerOrderResource;
use App\Models\Customer;
use App\Models\Factory;
use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'App name' => env('APP_NAME'),
        'Backend Version' => App::version(),
        'Environment' => App::environment()
    ], 201);
});

