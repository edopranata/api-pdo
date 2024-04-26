<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Models\Factory;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;

class DashboardController extends Controller
{

    public function index()
    {

        $auth = auth('api')->user();
        if($auth){
            $user = $auth->load('cash');

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
                'user' => UserResource::make($user),
                'factories' => $factories,
            ]);
        }else{
            abort(401, 'Unauthorized');
        }

    }
}
