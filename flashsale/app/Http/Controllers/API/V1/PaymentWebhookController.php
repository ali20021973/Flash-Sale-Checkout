<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Hold;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\JsonResponse;
 use App\Jobs\ProcessPaymentWebhookJob;

class PaymentWebhookController extends Controller
{
   

public function handle(Request $request): JsonResponse
{
    $validated = $request->validate([
        'idempotency_key' => 'required|string',
        'order_id' => 'required|integer',
        'status' => 'required|string|in:success,failure',
    ]);

    $orderId = $validated['order_id'];

    // If order not created yet → store in Redis
    if (!Order::find($orderId)) {
        Redis::rpush("pending_webhooks:order:{$orderId}", json_encode($validated));
        return response()->json(['message' => 'Order not created, queued'], 202);
    }

    // Dispatch job (concurrency-safe processing)
    ProcessPaymentWebhookJob::dispatch($validated);

    return response()->json(['message' => 'Webhook queued for processing'], 200);
}


    /**
     * Process any pending webhooks for a given order
     * Call this after creating an order
     */
        public static function processPendingWebhooks(Order $order)
    {
        $orderId = $order->id;

        while ($payload = Redis::lpop("pending_webhooks:order:{$orderId}")) {

            $data = json_decode($payload, true);

            ProcessPaymentWebhookJob::dispatch($data);
        }
    }

}
