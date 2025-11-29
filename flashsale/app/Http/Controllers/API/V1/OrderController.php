<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Hold;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'hold_id' => 'required|exists:holds,id',
        ]);

        $holdId = $request->hold_id;

        try {
            $order = DB::transaction(function () use ($holdId) {
                $hold = Hold::lockForUpdate()->findOrFail($holdId);

                if ($hold->status !== 'active' || $hold->isExpired()) {
                    return response()->json(['message' => 'Hold is invalid or expired'], 409);
                }

                // Mark hold as used
                $hold->status = 'used';
                $hold->save();

                // Create order in pre-payment state
                return Order::create([
                    'hold_id' => $hold->id,
                    'status' => 'pending_payment',
                ]);
            }, 5);

            if ($order instanceof JsonResponse) return $order;

            return response()->json([
                'order_id' => $order->id,
                'status' => $order->status,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create order', 'error' => $e->getMessage()], 500);
        }
    }
}
