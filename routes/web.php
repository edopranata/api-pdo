<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'Backend Version' => App::version(),
        'Environment' => App::environment()
    ], 201);
});
