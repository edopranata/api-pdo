<?php

namespace Database\Seeders;

use App\Http\Traits\InvoiceTrait;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    use InvoiceTrait;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $type = 'DO';

        $customers = Customer::query()
            ->with(['orders' => function ($query) {
                $query->whereNull('invoice_status');
            }])
            ->whereRelation('orders', 'invoice_status', '=', null)
            ->get();


        foreach ($customers as $customer) {

            $user = User::query()->withWhereHas('cash')->role('Cashier')->first();

            $orders = collect($customer->orders);
            $trade_date = $orders->last()->trade_date->addDay();
            $sequence = $this->getLastSequence($trade_date, $type);
            $invoice_number = 'TM' . $type . $trade_date->format('Y') . sprintf('%08d', $sequence);
            $total = $orders->sum('customer_total');

            if ($user->cash->balance > $total) {

                $invoice = $customer->invoices()->create([
                    'user_id' => $user->id,
                    'trade_date' => $trade_date,
                    'invoice_number' => $invoice_number,
                    'type' => $type,
                    'sequence' => $sequence,
                ]);

                foreach ($customer->orders as $order) {
                    $order->update([
                        'invoice_status' => $trade_date
                    ]);

                    $invoice->details()->create([
                        'order_id' => $order->id,
                    ]);
                }

                $user->cash->details()->create([
                    'balance' => $total * -1,
                    'transaction_type' => $invoice ? get_class(new Invoice()) : null,
                    'transaction_id' => $invoice ? $invoice->id : null,
                    'description' => "INV#$invoice_number DO $customer->name",
                    'opening_balance' => $user->cash->balance,
                    'trade_date' => $trade_date,
                    'user_id' => $user->id
                ]);

                $user->cash()->update([
                    'balance' => $user->cash->balance - $total
                ]);
            }
        }
    }
}
