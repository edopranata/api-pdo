<?php

namespace App\Http\Controllers\Api\Report;

use App\Exports\Customer\SingleCustomerOrdersReportExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerOrderReportResource;
use App\Http\Resources\Customer\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SingleCustomerOrderReportController extends Controller
{
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {

        $customers = Customer::query()->with('loan')->get();
        return CustomerResource::collection($customers);
    }

    public function show(Request $request): JsonResponse|CustomerOrderReportResource
    {
        $validator = Validator::make($request->only([
            'monthly', 'customer_id'
        ]), [
            'monthly' => ['required', 'date_format:Y/m'],
            'customer_id' => ['required', 'exists:customers,id']
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }
        $customer = $this->getFirst($request);

        if (isset($customer->orders)) {
            return CustomerOrderReportResource::make($customer);
        }else{
            return response()->json(['status' => false, 'errors' => ['monthly' => ['No record on this period']]], 422);
        }
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        $validator = Validator::make($request->only([
            'monthly', 'customer_id'
        ]), [
            'monthly' => ['required', 'date_format:Y/m'],
            'customer_id' => ['required', 'exists:customers,id']
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }else {
            $customer = $this->getFirst($request);
            if (isset($customer->orders)) {
                return Excel::download(new SingleCustomerOrdersReportExport($customer, $request), $request->get('file_name') ?? 'filename.xlsx');
            }else{
                return response()->json(['status' => false, 'errors' => ['monthly' => ['No record on this period']]], 422);
            }


        }
    }

    /**
     * @param Request $request
     * @return Customer|null
     */
    public function getFirst(Request $request): ?Customer
    {
        $monthly = str($request->get('monthly'))->split('#/#');

        $date = ['month' => $monthly[1], 'year' => $monthly[0]];

        $customer = Customer::query()
            ->where('id', $request->get('customer_id'))
            ->withWhereHas('orders', function ($query) use ($date) {
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
            ->first();
        return $customer;
    }
}
