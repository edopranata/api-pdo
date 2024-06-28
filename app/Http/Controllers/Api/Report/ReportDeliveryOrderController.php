<?php

namespace App\Http\Controllers\Api\Report;

use App\Exports\Transaction\AllDeliveryOrderReportExport;
use App\Exports\Transaction\DeliveryOrderReportExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Factory\FactoryResource;
use App\Http\Resources\Order\DeliveryOrderCollection;
use App\Http\Traits\OrderTrait;
use App\Models\Factory;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportDeliveryOrderController extends Controller
{
    use OrderTrait;

    public function index(): JsonResponse
    {
        $factory = Factory::all();
        return response()->json([
            'factories' => FactoryResource::collection($factory),
        ], 201);
    }

    public function show(Factory $factory, Request $request): JsonResponse
    {
        $validator = $this->validate($request);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $orders = $factory->order()
                ->with(['customer'])
                ->when($request->get('start_date'), function (Builder $builder, $start_date) {
                    $builder->whereDate('trade_date', '>=', $start_date);
                })
                ->when($request->get('end_date'), function (Builder $builder, $end_date) {
                    $builder->whereDate('trade_date', '<=', $end_date);
                })
                ->when($request->get('monthly'), function (Builder $builder, $monthly) {
                    $monthly = str($monthly)->split('#/#');

                    $builder
                        ->whereYear('trade_date', '=', $monthly[0])
                        ->whereMonth('trade_date', '=', $monthly[1]);
                })
                ->orderBy('trade_date')
                ->get();
            return response()->json([
                'orders' => DeliveryOrderCollection::make($orders),
            ], 201);
        }
    }

    public function export(Factory $factory, Request $request): BinaryFileResponse|JsonResponse
    {
        $validator = $this->validate($request);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        } else {
            return Excel::download(new DeliveryOrderReportExport($factory, $request), $request->get('file_name') ?? 'filename.xlsx');
        }
    }

    public function showAll(Request $request): JsonResponse
    {
        $validator = $this->validate($request);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $orders = Order::query()
                ->with(['customer', 'factory'])
                ->when($request->get('start_date'), function (Builder $builder, $start_date) {
                    $builder->whereDate('trade_date', '>=', $start_date);
                })
                ->when($request->get('end_date'), function (Builder $builder, $end_date) {
                    $builder->whereDate('trade_date', '<=', $end_date);
                })
                ->when($request->get('monthly'), function (Builder $builder, $monthly) {
                    $monthly = str($monthly)->split('#/#');

                    $builder
                        ->whereYear('trade_date', '=', $monthly[0])
                        ->whereMonth('trade_date', '=', $monthly[1]);
                })
                ->orderBy('trade_date')
                ->get();
            return response()->json([
                'orders' => DeliveryOrderCollection::make($orders),
            ], 201);
        }
    }

    public function exportAll(Request $request): BinaryFileResponse|JsonResponse
    {
        $validator = $this->validate($request);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        } else {
            return Excel::download(new AllDeliveryOrderReportExport($request), $request->get('file_name') ?? 'filename.xlsx');
        }
    }

    private function validate(Request $request): \Illuminate\Validation\Validator
    {
        if ($request->has('monthly')) {
            $validator = Validator::make($request->only([
                'monthly'
            ]), [
                'monthly' => ['required', 'date_format:Y/m'],
            ]);
        } else {
            $validator = Validator::make($request->only([
                'start_date', 'end_date'
            ]), [
                'start_date' => 'required|date|before_or_equal:end_date',
                'end_date' => 'required|date',
            ]);
        }

        return $validator;
    }
}
