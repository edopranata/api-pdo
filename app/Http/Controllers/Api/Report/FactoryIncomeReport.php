<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Models\Factory;
use App\Models\Income;
use Illuminate\Http\Request;

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
                ->orderBy('trade_date');

            $data = $query->paginate($request->get('limit', 10));
            return response()->json([
                'data' => $data,
            ], 201);
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

    }

    public function export(Income $income)
    {

    }
}
