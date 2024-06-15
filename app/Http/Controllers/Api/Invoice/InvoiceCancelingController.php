<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Resources\Report\InvoiceDataCollection;
use App\Http\Resources\Report\InvoiceDataResource;
use App\Http\Traits\CashTrait;
use App\Models\Invoice;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceCancelingController extends Controller
{

    use CashTrait;

    public function index(Request $request): InvoiceDataCollection
    {
        $invoices = Invoice::query()
            ->with(['orders', 'installment', 'customer'])
            ->when($request->get('search'), function (Builder $query, $search) {
                $query->whereRelation('customer', 'name', "like", "%$search%");
                $query->orWhere('invoice_number', 'like', "%$search%");
            })
            ->when($request->get('sortBy'), function ($query, $sort) {
                $sortBy = collect(json_decode($sort));
                return $query->orderBy($sortBy['key'], $sortBy['order']);
            })
            ->when(!$request->get('sortBy'), function ($query) {
                return $query->orderByDesc('id');
            })
            ->paginate($request->get('limit', 10));
        return new InvoiceDataCollection($invoices);
    }

    public function show(Invoice $invoice): InvoiceDataResource
    {
        return InvoiceDataResource::make($invoice->load(['orders', 'installment', 'customer']));
    }

    public function destroy(Invoice $invoice, Request $request): JsonResponse
    {
        $invoice->load(['orders', 'installment', 'customer']);
        $user = auth('api')->user()->load('cash');

        $trade_date = now();
        $invoice_number = $invoice->invoice_number;
        $customer_name = $invoice->customer->name;
        $order_total = $invoice->orders->sum('customer_total');
        $installment = $invoice->installment?->balance ?? 0;
        $customer_total = $order_total + $installment;

        $cash = $user->cash?->balance ?? 0;
        $loan = $invoice->customer->loan()->first();

        if (!$user->cash) {
            return response()->json(['status' => false, 'errors' => ['balance' => ['User has no balance']]], 422);
        }

        $cash_validation = $cash + $customer_total;

        if($cash_validation < 0){
            return response()->json(['status' => false, 'errors' => ['balance' => ["Invalid cash balance user $user->username only have Rp. $cash"]]], 422);
        }

        DB::beginTransaction();
        $description = "Canceling INV#$invoice_number Name $customer_name";
        try {
            // Restore Cash User
            if ($customer_total > 0) {
                $this->incrementCash($customer_total, $trade_date, $description, $invoice);
            } else {
                $this->decrementCash($customer_total, $trade_date, $description, $invoice);
            }

            // Create Loan Details
            if ($installment !== 0) {
                $loan_installment = $installment * -1;

                $loan->details()->create([
                    'balance' => $loan_installment,
                    'description' => $description,
                    'opening_balance' => $loan->balance,
                    'trade_date' => $trade_date,
                    'user_id' => auth('api')->id()
                ]);

                $loan->update([
                    'balance' => $loan->balance + $loan_installment,
                ]);
            }

            $invoice->orders()->delete();
            $invoice->delete();

            DB::commit();
            return response()->json(['status' => true], 201);
        } catch (Exception $exception) {
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
