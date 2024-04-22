<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\DeliveryOrderCollection;
use App\Http\Traits\OrderTrait;
use Illuminate\Http\Request;

class ReportDeliveryOrderController extends Controller
{
    use OrderTrait;
    public function index(Request $request)
    {
        $order = $this->getOrderTable($request);
        return DeliveryOrderCollection::make($order);
    }
}
