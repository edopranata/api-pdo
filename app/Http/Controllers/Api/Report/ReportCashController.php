<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReportCashController extends Controller
{


    public function show(Request $request): AnonymousResourceCollection|UserResource
    {

        if($request->has('user_id')){
            $data = User::query()->find($request->get('user_id'));
            $user = $data->load(['mutations' => function($query) use ($request) {
                if($request->has('date')){
                    return $query->whereDate('cash_details.created_at', Carbon::createFromFormat('Y/m/d', $request->get('date')));
                }else{
                    return $query->whereDate('cash_details.created_at', Carbon::today());
                }
            }]);
            return UserResource::make($user);
        }else{
            $user = auth('api')->user();
            if($user->hasRole('Cashier')){
                return UserResource::make($user->load('cash'));
            }else{
                $user = User::query()->with('cash')->role('Cashier')->get();
                return UserResource::collection($user);
            }
        }
    }
}
