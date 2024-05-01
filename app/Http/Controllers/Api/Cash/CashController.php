<?php

namespace App\Http\Controllers\Api\Cash;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cash\CashCollection;
use App\Http\Resources\Cash\CashDetailCollection;
use App\Http\Resources\Cash\CashResource;
use App\Http\Resources\User\UserResource;
use App\Models\Cash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CashController extends Controller
{

    public function index(Request $request): CashCollection
    {
        $query = User::query()
            ->role('Cashier')
            ->with('cash')
            ->when($request->get('search'), function ($query, $search) {
                return $query->where('name', 'LIKE', "%$search%");
            })
            ->when($request->get('search'), function ($query, $search) {
                return $query->orWhere('username', 'LIKE', "%$search%");
            })
            ->when($request->get('search'), function ($query, $search) {
                return $query->orWhere('email', 'LIKE', "%$search%");
            })
            ->when($request->get('sortBy'), function ($query, $sort) {
                $sortBy = collect(json_decode($sort));
                return $query->orderBy($sortBy['key'], $sortBy['order']);
            });

        $data = $request->get('limit', 0) > 0 ? $query->paginate($request->get('limit', 10)) : $query->get();

        return new CashCollection($data);
    }

    public function show(User $user, Request $request)
    {
        $cash = $user->cash()->first();
        $query = $cash->details()->when($request->get('search'), function ($query, $search) {
            return $query->where('name', 'LIKE', "%$search%");
        })
            ->when($request->get('search'), function ($query, $search) {
                return $query->orWhere('username', 'LIKE', "%$search%");
            })
            ->when($request->get('search'), function ($query, $search) {
                return $query->orWhere('email', 'LIKE', "%$search%");
            })->orderBy('created_at', 'desc');

        $data = $query->paginate($request->get('limit', 10));

        return response()->json([
            'details' => new CashDetailCollection($data),
            'user' => new UserResource($user),
            'cash' => new CashResource($cash),
        ]);
    }

    public function giveCash(User $user, Request $request)
    {

        $cash = $user->cash();
        $details = $cash->first();

        $trade_date = now();
        $validator = Validator::make($request->only([
            'balance',
        ]), [
            'balance' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }


        $description = $request->get('description') ?? 'Tambahan Kas';

        DB::beginTransaction();
        try {
            if ($cash->exists()) {
                $details->details()->create([
                    'balance' => $request->balance,
                    'description' => $description,
                    'opening_balance' => $details->balance,
                    'trade_date' => $trade_date,
                    'user_id' => auth('api')->id()
                ]);

                $details->update([
                    'balance' => $details->balance + $request->balance
                ]);

            } else {
                $details = $cash->create([
                    'balance' => $request->balance,
                ]);

                $details->details()->create([
                    'balance' => $request->balance,
                    'description' => $description,
                    'opening_balance' => 0,
                    'trade_date' => $trade_date,
                    'user_id' => auth('api')->id()
                ]);
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }

    public function takeCash(User $user, Request $request)
    {
        $cash = $user->cash();
        $details = $cash->first();
        $balance = $details ? $details->balance : 0;
        $validator = Validator::make($request->only([
            'balance',
        ]), [
            'balance' => 'required|numeric|min:1|max:' . $balance,
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }
        $description = $request->get('description') ?? 'Setoran Kas';

        DB::beginTransaction();
        try {
            if ($cash->exists()) {

                $details->details()->create([
                    'balance' => $request->balance * -1,
                    'opening_balance' => $details->balance,
                    'description' => $description,
                    'trade_date' => now(),
                    'user_id' => auth('api')->id()
                ]);

                $details->update([
                    'balance' => $details->balance - $request->balance
                ]);
            } else {
                return response()->json(['status' => false, 'errors' => ['balance' => ['User has no balance']]], 422);
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }
}
