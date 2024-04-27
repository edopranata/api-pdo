<?php

namespace App\Http\Controllers\Api\Price;

use App\Http\Controllers\Controller;
use App\Models\Factory;
use App\Models\FactoryPrice;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FactoryPriceController extends Controller
{
    public function index(): JsonResponse
    {
        $day = 30;
        $now = now();
        $periods = CarbonPeriod::create($now->subDays($day), now());
        $factories = Factory::query()->with(['prices' => function ($builder) use ($periods) {
            $builder->whereDate('date', '>=', $periods->first()->format('Y/m/d'))->orderBy('date', 'desc');
        }])->get()->map(function ($factory) {
            return [
                'id' => $factory->id,
                'name' => $factory->name,
                'event' => $factory->prices->pluck('date')->map(function ($date) {
                    return Carbon::parse($date)->format('Y/m/d');
                }),
                'price' => $factory->prices->map(function ($price) {
                    return [
                        'id' => $price->id,
                        'date' => Carbon::create($price->date)->format('Y/m/d'),
                        'price' => $price->price,
                    ];
                }),
            ];
        });

        $date = collect($periods->toArray())->map(function ($period) {
            return Carbon::create($period)->format('Y/m/d');
        });

        return response()->json([
            'day' => $day,
            'start_date' => $periods->first()->format('Y/m/d'),
            'end_date' => $periods->last()->format('Y/m/d'),
            'period' => $date,
            'factories' => $factories,
        ]);
    }

    public function store(Factory $factory, Request $request)
    {
        $validator = Validator::make($request->only([
            'price_date', 'price',
        ]), [
            'price_date' => 'required|date|before_or_equal:' . Carbon::now()->toDateString(),
            'price' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }

        DB::beginTransaction();
        try {

            $factory->prices()->create([
                'date' => $request->get('price_date'),
                'price' => $request->get('price'),
            ]);

            DB::commit();

            return response()->json(['status' => true], 201);

        } catch (\Exception $exception) {
            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }

    public function update(FactoryPrice $price, Request $request)
    {
        $validator = Validator::make($request->only([
            'price',
        ]), [
            'price' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }

        DB::beginTransaction();
        try {

            $price->update([
                'price' => $request->get('price'),
            ]);

            DB::commit();

            return response()->json(['status' => true], 201);

        } catch (\Exception $exception) {
            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }
}
