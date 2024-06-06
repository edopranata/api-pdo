<?php

namespace App\Http\Controllers\Api;

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

class BlankController extends Controller
{

    public function index(): JsonResponse
    {
        return response()->json([
            'status' => true
        ], 201);
    }

    public function test(): JsonResponse
    {
        $cash = User::query()->where('id','9c06500e-2141-4ecc-9b99-70e69875047a')->with(['invoices'])
            ->first();

        return response()->json([
//            'user' => UserResource::make($user),
            'cash' => $cash ? CashResource::make($cash) : null,
        ], 201);
    }
}
