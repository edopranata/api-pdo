<?php

namespace App\Http\Controllers\Api\Loan;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerCollection;
use App\Http\Resources\Report\InvoiceDataResource;
use App\Http\Traits\CashTrait;
use App\Http\Traits\InvoiceTrait;
use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{
    use InvoiceTrait, CashTrait;

    public function index(Request $request): CustomerCollection
    {
        $query = Customer::query()
            ->with('loan')
            ->when($request->get('search'), function ($query, $search) {
                return $query->where('name', 'LIKE', "%$search%");
            })
            ->when($request->get('search'), function ($query, $search) {
                return $query->orWhere('phone', 'LIKE', "%$search%");
            })
            ->when($request->get('search'), function ($query, $search) {
                return $query->orWhere('address', 'LIKE', "%$search%");
            })
            ->when($request->get('sortBy'), function ($query, $sort) {
                $sortBy = collect(json_decode($sort));
                return $query->orderBy($sortBy['key'], $sortBy['order']);
            });

        $data = $query->paginate($request->get('limit', 10));

        return new CustomerCollection($data);
    }

    public function addLoan(Customer $customer, Request $request)
    {
        $loan = $customer->loan();
        $detail = $loan->first();

        $cash = auth('api')->user()->cash()->first();
        $trade_date = now();

        $validator = Validator::make($request->only([
            'balance',
        ]), [
            'balance' => ['required', 'numeric', 'min:1',
                function (string $attribute, mixed $value, Closure $fail) use ($cash) {
                    $cash = $cash ? max($cash->balance, 0) : 0;

                    if ($value > $cash) {
                        $fail("Cash {$attribute} is {$cash}.");
                    }
                }],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }

        DB::beginTransaction();
        try {
            $type = 'LN';
            $sequence = $this->getLastSequence($trade_date, $type);
            $invoice_number = 'TM' . $type . $trade_date->format('Y') . sprintf('%08d', $sequence);

            if ($loan->exists()) {
                $details = $detail->details()->create([
                    'balance' => $request->balance,
                    'opening_balance' => $detail->balance,
                    'trade_date' => $trade_date,
                    'user_id' => auth('api')->id()
                ]);

                $detail->update([
                    'balance' => $detail->balance + $request->balance
                ]);

                $invoice = $customer->invoices()
                    ->create([
                        'user_id' => auth()->id(),
                        'trade_date' => $trade_date,
                        'invoice_number' => $invoice_number,
                        'type' => $type,
                        'sequence' => $sequence,
                    ]);

                // Create Invoice Loan
                $invoice->loan()->create([
                    'loan_detail_id' => $details->id
                ]);
            } else {
                $detail = $loan->create([
                    'balance' => $request->balance,
                    'user_id' => auth('api')->id()
                ]);

                $details = $detail->details()->create([
                    'balance' => $request->balance,
                    'opening_balance' => 0,
                    'trade_date' => $trade_date,
                    'user_id' => auth('api')->id()
                ]);

                $invoice = $customer->invoices()
                    ->create([
                        'user_id' => auth('api')->id(),
                        'trade_date' => $trade_date,
                        'invoice_number' => $invoice_number,
                        'type' => $type,
                        'sequence' => $sequence,
                    ]);

                // Create Invoice Loan
                $invoice->loan()->create([
                    'loan_detail_id' => $details->id
                ]);
            }

            $this->decrementCash($request->balance, $trade_date,  "Pinjaman $customer->name", $invoice);

            DB::commit();

            return InvoiceDataResource::make($invoice->load(['orders', 'installment', 'customer']));
        } catch (\Exception $exception) {

            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }

    public function payLoan(Customer $customer, Request $request)
    {
        $loan = $customer->loan();
        $detail = $loan->first();

        $trade_date = now();

        $balance =  $loan->exists() ? $detail->balance : 0;
        $validator = Validator::make($request->only([
            'balance',
        ]), [
            'balance' => 'required|numeric|min:1|max:' . $balance
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()->toArray()], 422);
        }

        DB::beginTransaction();
        try {
            $type = 'LN';
            $sequence = $this->getLastSequence($trade_date, $type);
            $invoice_number = 'TM' . $type . $trade_date->format('Y') . sprintf('%08d', $sequence);

            if ($loan->exists()) {
                $details = $detail->details()->create([
                    'balance' => $request->balance * -1,
                    'opening_balance' => $detail->balance,
                    'trade_date' => $trade_date,
                    'user_id' => auth('api')->id()
                ]);

                $detail->update([
                    'balance' => $detail->balance - $request->balance
                ]);

                $invoice = $customer->invoices()
                    ->create([
                        'user_id' => auth('api')->id(),
                        'trade_date' => $trade_date,
                        'invoice_number' => $invoice_number,
                        'type' => $type,
                        'sequence' => $sequence,
                    ]);

                // Create Invoice Loan
                $invoice->loan()->create([
                    'loan_detail_id' => $details->id
                ]);

                $this->incrementCash($request->balance, $trade_date, "Angsuran Pinjaman $customer->name", $invoice);

            } else {
                return response()->json(['status' => false, 'errors' => ['balance' => ['Customer has no loan']]], 422);
            }

            DB::commit();

            return InvoiceDataResource::make($invoice->load(['orders', 'installment', 'customer']));

        } catch (\Exception $exception) {

            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }
}
