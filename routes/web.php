<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return response()->json([
        'App Version' => \Illuminate\Support\Facades\App::version()
    ], 201);
});

Route::get('/web', function (){
    $permissions = collect(Route::getRoutes())
        ->whereNotNull('action.as')
        ->map(function ($route) {
            $action = collect($route->action)->toArray();
            $method = collect($route->methods)->first();
            $as = str($action['as'])->lower();
            if ($as->startsWith('admin') && !$as->endsWith('.')) {
                $name = Str::replace('admin.', '', $action['as']);
                return [
                    'method' => $method,
                    'name' => $action['as'],
                    'parent' => \str(collect(\str($name)->explode('.'))[0])->headline(),
                    'children' => \str(collect(\str($name)->explode('.'))[1])->headline(),
                    'title' => \str(collect(\str($name)->explode('.'))[2])->headline(),
                    'path' => $route->uri
                ];
            }else {
                return null;
            }
        })
        ->filter(function ($value) {
            return !is_null($value);
        });

    return $permissions;
});
