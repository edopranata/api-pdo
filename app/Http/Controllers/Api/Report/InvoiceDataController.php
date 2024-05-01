<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Http\Resources\Report\InvoiceDataCollection;
use App\Http\Resources\Report\InvoiceDataResource;
use App\Http\Traits\InvoiceTrait;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class InvoiceDataController extends Controller
{
    use InvoiceTrait;

    public function index(Request $request): InvoiceDataCollection
    {
        $invoices = Invoice::query()
            ->with(['orders', 'installment', 'customer'])
            ->when($request->get('search'), function ($query, $search) {
                $query->whereRelation('customer', 'name', $search);
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

    public function show(Invoice $invoice)
    {
        return InvoiceDataResource::make($invoice->load(['orders.factory', 'installment', 'customer']));
    }
}
