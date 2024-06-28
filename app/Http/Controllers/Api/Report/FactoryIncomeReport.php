<?php

namespace App\Http\Controllers\Api\Report;

use App\Exports\Transaction\DeliveryOrderReportExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Income\IncomeDataCollection;
use App\Http\Resources\Income\IncomeDataResource;
use App\Models\Factory;
use App\Models\Income;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FactoryIncomeReport extends Controller
{

    public function index(Request $request)
    {
        if($request->has('limit')){
            $query = Income::query()
                ->with(['factory'])
                ->when($request->get('factory_id'), function ($query, $factory_id) {
                    $query->where('factory_id', $factory_id);
                })
                ->when($request->get('sortBy'), function ($query, $sort) {
                    $sortBy = collect(json_decode($sort));
                    return $query->orderBy($sortBy['key'], $sortBy['order']);
                })
                ->orderBy('trade_date');

            $data = $query->paginate($request->get('limit', 10));
            return IncomeDataCollection::make($data);
        }else{
            $query = Factory::all()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                ];
            });

            return response()->json([
                'factories' => $query
            ], 201);
        }
    }

    public function show(Income $income)
    {
        return IncomeDataResource::make($income->load(['factory', 'orders.customer']));
    }

    public function export(Income $income, Request $request)
    {
        return Excel::download(new DeliveryOrderReportExport($income->factory()->first(), $request), $request->get('file_name') ?? 'filename.xlsx');

    }
}
