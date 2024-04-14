<?php

namespace App\Http\Traits;

use App\Models\Invoice;
use Carbon\Carbon;

trait InvoiceTrait
{
    private function getLastSequence($invoice_date, string $type) {
        $date = Carbon::parse($invoice_date);
        $invoice = Invoice::query()
            ->where('type', $type
            )
            ->whereYear('trade_date', $date->format('Y'))
            ->latest('id')->first();
        return $invoice ? ($invoice->sequence + 1) : 1;

    }
}
