<?php

namespace App\Http\Traits;

use App\Models\Cash;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

trait CashTrait
{

    private function incrementCash($amount, $trade_date, $invoice = null): void
    {
        $cash = auth('api')->user()->cash();
        DB::beginTransaction();
        try {
            if ($cash->exists()) {
                $details = $cash->first();
                $details->details()->create([
                    'balance' => $amount,
                    'transaction_type' => $invoice ? get_class(new Invoice()) : null,
                    'transaction_id' => $invoice ? $invoice->id : null,
                    'opening_balance' => $details->balance,
                    'trade_date' => $trade_date,
                    'user_id' => auth('api')->id()
                ]);

                $details->update([
                    'balance' => $details->balance + $amount
                ]);

            } else {
                $details = $cash->create([
                    'balance' => $amount,
                ]);

                $details->details()->create([
                    'balance' => $amount,
                    'transaction_type' => $invoice ? get_class(new Invoice()) : null,
                    'transaction_id' => $invoice ? $invoice->id : null,
                    'opening_balance' => 0,
                    'trade_date' => $trade_date,
                    'user_id' => auth('api')->id()
                ]);
            }
            DB::commit();
        }catch (\Exception $exception){

            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());

        }

    }

    private function decrementCash($amount, $trade_date, $invoice = null): void
    {
        $cash = auth('api')->user()->cash();
        DB::beginTransaction();
        try {
            if ($cash->exists()) {
                $details = $cash->first();
                $details->details()->create([
                    'balance' => $amount * -1,
                    'transaction_type' => $invoice ? get_class(new Invoice()) : null,
                    'transaction_id' => $invoice ? $invoice->id : null,
                    'opening_balance' => $details->balance,
                    'trade_date' => $trade_date,
                    'user_id' => auth('api')->id()
                ]);

                $details->update([
                    'balance' => $details->balance - $amount
                ]);

            } else {
                $details = $cash->create([
                    'balance' => $amount,
                    'user_id' => auth('api')->id()
                ]);

                $details->details()->create([
                    'balance' => $amount,
                    'transaction_type' => $invoice ? get_class(new Invoice()) : null,
                    'transaction_id' => $invoice ? $invoice->id : null,
                    'opening_balance' => 0,
                    'trade_date' => $trade_date,
                    'user_id' => auth('api')->id()
                ]);
            }
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            abort(403, $exception->getCode() . ' ' . $exception->getMessage());
        }
    }
}
