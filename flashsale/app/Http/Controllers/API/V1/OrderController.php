<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Create an order from a hold
     */
    public function store(Request $request, OrderService $orderService)
{
    $request->validate(['hold_id' => 'required|integer']);

    try {
        $order = $orderService->createOrderFromHold($request->hold_id);

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'status' => $order->status,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

}
