<?php

namespace App\Http\Controllers\Api\Report;

use App\Exports\Income\AllFactoryIncomeExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Income\IncomeDataCollection;
use App\Models\Income;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AllFactoryIncomeReport extends Controller
{


    public function show(Request $request): JsonResponse
    {
        $income = Income::query()
            ->with(['factory'])
            ->when($request->get('monthly'), function ($query, $monthly) {
                $monthly = str($monthly)->split('#/#');

                $query
                    ->whereYear('trade_date', '=', $monthly[0])
                    ->whereMonth('trade_date', '=', $monthly[1]);
            })
            ->orderBy('trade_date')
            ->get();

        return response()->json([
            'income' => IncomeDataCollection::make($income)
        ], 201);

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

        return Excel::download(new AllFactoryIncomeExport($monthly), 'income_report_' . $monthly[0] . $monthly[1] . '.xlsx');
    }
}
