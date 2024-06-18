<?php

namespace App\Http\Controllers\Api\Report;

use App\Exports\Customer\CustomerOrderReportExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerCollection;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerOrderReportController extends Controller
{

    public function show(Request $request): JsonResponse|CustomerCollection
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
        $customer = Customer::query()
            ->whereHas('orders', function ($query) use ($date) {
                $query->whereMonth('trade_date', $date['month'])->whereYear('trade_date', $date['year']);
            })
            ->withCount(['orders' => function ($query) use ($date) {
                $query->whereMonth('trade_date', $date['month'])->whereYear('trade_date', $date['year']);
            }])
            ->withSum(['orders' => function ($query) use ($date) {
                $query->whereMonth('trade_date', $date['month'])->whereYear('trade_date', $date['year']);
            }], 'net_weight')
            ->withAvg(['orders' => function ($query) use ($date) {
                $query->whereMonth('trade_date', $date['month'])->whereYear('trade_date', $date['year']);
            }], 'customer_price')
            ->withSum(['orders' => function ($query) use ($date) {
                $query->whereMonth('trade_date', $date['month'])->whereYear('trade_date', $date['year']);
            }], 'customer_total')
            ->get();

        return new CustomerCollection($customer);
    }

    public function export(Request $request): BinaryFileResponse|JsonResponse
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

        return Excel::download(new CustomerOrderReportExport($monthly), 'customer_order_' . $monthly[0] . $monthly[1] . '.xlsx');

    }
}
