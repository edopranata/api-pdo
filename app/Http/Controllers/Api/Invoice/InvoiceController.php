<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerOrderCollection;
use App\Http\Resources\Customer\CustomerResource;
use App\Http\Resources\Order\DeliveryOrderCollection;
use App\Http\Resources\Report\InvoiceDataResource;
use App\Http\Traits\CashTrait;
use App\Http\Traits\InvoiceTrait;
use App\Http\Traits\OrderTrait;
use App\Models\Customer;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    use OrderTrait, CashTrait, InvoiceTrait;

    public function index(Request $request): CustomerOrderCollection
    {
        $orders = Customer::query()
            ->with(['orders' => function ($query) {
                $query->whereNull('invoice_status');
            }])
            ->whereRelation('orders', 'invoice_status', '=', null)
            ->paginate($request->get('limit', 10));
        return new CustomerOrderCollection($orders);

    }

    public function show(Customer $customer, Request $request): JsonResponse
    {
        if ($request->has('limit') || $request->has('page')) {
            $orders = $customer->orders()
                ->whereNull('invoice_status')
                ->with(['factory'])
                ->when($request->get('factory_id'), function ($query, $factoryId) {
                    $query->where('factory_id', $factoryId);
                })
                ->when($request->get('sortBy'), function ($query, $sort) {
                    $sortBy = collect(json_decode($sort));
                    return $query->orderBy($sortBy['key'], $sortBy['order']);
                })
                ->when(!$request->get('sortBy'), function ($query) {
                    return $query->orderByDesc('id');
                })
                ->paginate($request->get('limit', 10));
            return response()->json([
                'orders' => new DeliveryOrderCollection($orders)
            ]);
        } else {
            return response()->json([
                'customer' => new CustomerResource($customer->load(['loan']))
            ]);
        }
    }

    public function store(Customer $customer, Request $request)
    {
        $now = now();
        $loan_balance = $customer->loan()->exists() ? $customer->loan()->first()->balance : 0;

        $user = auth('api')->user();

        $cash = $user->cash()->exists() ? $user->cash()->first()->balance : 0;

        if ($loan_balance > 0) {
            $validator = Validator::make($request->only([
                'order_id', 'trade_date', 'installment', 'total'
            ]), [
                'order_id' => 'required:array|min:1',
                'order_id.*' => ['required', 'string', 'distinct', 'min:1', Rule::exists('orders', 'id')->whereNull('invoice_status')->withoutTrashed()],
                'trade_date' => 'required|date|before_or_equal:' . $now->toDateString(),
                'installment' => ['numeric', 'min:0', 'max:' . $loan_balance],
                'total' => ['required', 'numeric', 'min:0',
                    function (string $attribute, mixed $value, Closure $fail) use ($cash) {
                        $cash = $cash ? max($cash, 0) : 0;

                        if ($value > $cash) {
                            $fail("Cash balance is {$cash}.");
                        }
                    }],
            ]);
        } else {
            $validator = Validator::make($request->only([
                'order_id', 'trade_date', 'installment', 'total'
            ]), [
                'order_id' => 'required:array|min:1',
                'order_id.*' => ['required', 'string', 'distinct', 'min:1', Rule::exists('orders', 'id')->whereNull('invoice_status')->withoutTrashed()],
                'trade_date' => 'required|date|before_or_equal:' . $now->toDateString(),
                'installment' => [Rule::requiredIf($request->get('loan'))],
                'total' => ['required', 'numeric', 'min:0',
                    function (string $attribute, mixed $value, Closure $fail) use ($cash) {
                        $cash = $cash ? max($cash, 0) : 0;

                        if ($value > $cash) {
                            $fail("Cash balance is {$cash}.");
                        }
                    }],
            ]);
        }

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }

        DB::beginTransaction();
        try {
            /**
             * 1. Invoice
             *    - Insert Into Invoice
             *    - Insert Into Invoice Detail from selected orders
             *    - Update invoice_status from orders
             * 2. If invoice has installment
             *    - Insert into loan details
             *    - Decrement balance on loans
             *    - Insert into invoice loan
             * 3. Cash
             *    - Decrement Cash
             */
            $trade_date = Carbon::create($request->get('trade_date'));
            $type = 'DO';
            $sequence = $this->getLastSequence($trade_date, $type);
            $invoice_number = 'TM' . $type . $trade_date->format('Y') . sprintf('%08d', $sequence);
            $installment = $request->get('installment');
            $total = $request->get('total');

            $invoice = $customer->invoices()->create([
                'user_id' => auth('api')->id(),
                'trade_date' => $trade_date,
                'invoice_number' => $invoice_number,
                'type' => $type,
                'sequence' => $sequence,
            ]);

            $orders = $customer->orders()->whereIn('id', $request->get('order_id'))->get();

            foreach ($orders as $order) {
                $order->update([
                    'invoice_status' => $trade_date
                ]);

                $invoice->details()->create([
                    'order_id' => $order->id,
                ]);
            }

            $this->decrementCash($total, $trade_date, "INV#$invoice_number DO $customer->name", $invoice);

            $loan = $customer->loan()->first();

            if($installment >= 0) {
                if ($loan) {
                    // Add to loan details
                    $details = $loan->details()
                        ->create([
                            'user_id' => auth('api')->id(),
                            'trade_date' => $trade_date,
                            'description' => "Potong DO #$invoice->invoice_number",
                            'opening_balance' => $loan->balance,
                            'balance' => $installment * -1
                        ]);

                    // Update balance in loan table

                    $loan->update([
                        'balance' => $loan->balance - $installment
                    ]);

                    // Create Invoice Loan
                    $invoice->loan()->create([
                        'loan_detail_id' => $details->id
                    ]);
                }
            }
            DB::commit();

            return InvoiceDataResource::make($invoice->load(['orders', 'installment', 'customer']));

        } catch (\Exception $exception) {
            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }
}
