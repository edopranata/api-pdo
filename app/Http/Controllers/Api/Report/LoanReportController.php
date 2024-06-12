<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class LoanReportController extends Controller
{
    public function index(Request $request)
    {
        return $request->all();
    }

    public function show(Customer $customer)
    {
        return $customer;
    }

    public function export(Customer $customer)
    {
        return $customer;
    }
}
