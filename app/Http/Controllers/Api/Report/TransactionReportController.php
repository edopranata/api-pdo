<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionReportController extends Controller
{

    public function show(Request $request)
    {

        if ($request->has('user_id')) {
            $data = User::query()->find($request->get('user_id'));

            $user = $data->load(['invoices' => function ($query) use ($request) {
                if ($request->has('date')) {
                    return $query->with('customer')->whereDate('trade_date', Carbon::createFromFormat('Y/m/d', $request->get('date')));
                } else {
                    return $query->with('customer')->whereDate('trade_date', Carbon::today());
                }
            }]);
            return UserResource::make($user);
        } else {
            $user = auth('api')->user();
            if ($user->hasRole('Cashier')) {
                return UserResource::make($user->load('cash'));
            } else {
                $user = User::query()->with('cash')->role('Cashier')->get();
                return UserResource::collection($user);
            }
        }
    }
}
