<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerOrderResource;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerOrderReportController extends Controller
{
    public function index(Request $request)
    {
        return $request->all();
    }

    public function show(Customer $customer, Request $request)
    {
        $validator = Validator::make($request->only([
            'monthly'
        ]), [
            'monthly' => ['required', 'date_format:Y/m'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }
        $monthly = str($request->get('monthly'))->split('#/#');

        $date = ['month' => $monthly[1], 'year' => $monthly[0]];
        $customer =  $customer->load(['orders' => function ($query) use ($date) {
            $query->whereMonth('trade_date', $date['month'])->whereYear('trade_date', $date['year']);
        }]);

        return new CustomerOrderResource($customer);
    }

    public function export(Customer $customer)
    {
        return $customer;
    }
}
