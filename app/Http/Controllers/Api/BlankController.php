<?php

namespace App\Http\Controllers\Api;

use App\Exports\Transaction\DeliveryOrderReportExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Cash\CashDetailResource;
use App\Http\Resources\Cash\CashResource;
use App\Http\Resources\User\UserResource;
use App\Models\Cash;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BlankController extends Controller
{

    public function index(): JsonResponse
    {
        return response()->json([
            'status' => true
        ], 201);
    }

    public function test(Request $request)
    {
        return response()->json([
            'user' => auth('api')->user()->getAuthIdentifier(),
        ], 201);
    }
}
