<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Hold;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request) // <- inject Request here
    {
        // Validate input
        $validated = $request->validate([
            'idempotency_key' => 'required|string',
            'order_id' => 'required|integer',
            'status' => 'required|string|in:success,failure',
        ]);

        $order = Order::find($validated['order_id']);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Idempotency: check if this webhook was already processed
        if ($order->idempotency_key === $validated['idempotency_key']) {
            return response()->json(['message' => 'Webhook already processed'], 200);
        }

        $order->idempotency_key = $validated['idempotency_key'];

        if ($validated['status'] === 'success') {
            $order->status = 'paid';
        } else {
            $order->status = 'cancelled';
            // Release hold
            if ($order->hold) {
                $order->hold->status = 'active';
                $order->hold->save();
            }
        }

        $order->save();

        return response()->json(['message' => 'Webhook processed successfully']);
    }
}
