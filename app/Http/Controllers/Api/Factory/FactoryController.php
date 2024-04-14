<?php

namespace App\Http\Controllers\Api\Factory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Factory\FactoryCollection;
use App\Http\Resources\Factory\FactoryResource;
use App\Models\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FactoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Factory::query()
            ->when($request->get('name'), function ($query, $search) {
                return $query->where('name', 'LIKE', "%$search%");
            })
            ->when($request->get('sortBy'), function ($query, $sort) {
                $sortBy = collect(json_decode($sort));
                return $query->orderBy($sortBy['key'], $sortBy['order']);
            });

        $data = $request->get('limit', 0) > 0 ? $query->paginate($request->get('limit', 10)) : $query->get();

//        return $data;
        return new FactoryCollection($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->only([
                'name',
            ]), [
                'name' => 'required|string|min:3|max:30',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
            }

            $factory = Factory::query()
                ->create([
                    'name' => $request->name,
                    'margin' => $request->margin,
                    'price' => $request->price,
                    'ppn_tax' => $request->ppn_tax,
                    'pph22_tax' => $request->pph22_tax,
                    'user_id' => auth('api')->id()
                ]);

            DB::commit();

            return new FactoryResource($factory->load('user'));

        } catch (\Exception $exception) {
            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Factory $factory)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->only([
                'name',
            ]), [
                'name' => 'required|string|min:3|max:30',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
            }

            $factory->update([
                'name' => $request->name,
                'margin' => $request->margin,
                'price' => $request->price,
                'ppn_tax' => $request->ppn_tax,
                'pph22_tax' => $request->pph22_tax,
                'user_id' => auth()->id()
            ]);

            DB::commit();

            return new FactoryResource($factory->load('user'));

        } catch (\Exception $exception) {
            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Factory $factory, Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $factorys = $request->factory_id;
            if (is_array($factorys)) {
                Factory::query()
                    ->whereIn('id', $request->factory_id)->delete();
            } else {
                $factory->delete();
            }

            DB::commit();
            return response()->json(['status' => true], 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'error' => [
                    'code' => $exception->getCode(),
                    'massage' => $exception->getMessage()
                ]
            ], 301);
        }
    }
}
