<?php

namespace App\Http\Controllers\Api\Report;

use App\Exports\Customer\CustomerLoanExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerCollection;
use App\Models\Customer;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LoanReportController extends Controller
{
    public function show(): CustomerCollection
    {
        return new CustomerCollection(Customer::query()->withWhereHas('loan')->get());
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new CustomerLoanExport(), 'customer_loan_' . now()->format('Ymd') .'.xlsx');

    }
}
