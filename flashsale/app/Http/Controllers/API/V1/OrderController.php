<?php
namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Hold;
use App\Models\Order;
use App\Http\Controllers\Api\V1\PaymentWebhookController;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate(['hold_id' => 'required|integer|exists:holds,id']);

        $holdId = $request->hold_id;

        try {
            $order = DB::transaction(function () use ($holdId) {
                $hold = Hold::lockForUpdate()->findOrFail($holdId);

                if ($hold->status !== 'active' || $hold->isExpired()) {
                    abort(409, 'Hold is invalid or expired');
                }

                // mark hold as used
                $hold->status = 'used';
                $hold->save();

                // create order
                $order = Order::create([
                    'hold_id' => $hold->id,
                    'status' => 'pending_payment',
                ]);

                return $order;
            }, 5);

            // process any pending webhooks that arrived before order creation
            \App\Http\Controllers\Api\V1\PaymentWebhookController::processPendingWebhooks($order);

            return response()->json([
                'order_id' => $order->id,
                'status' => $order->status,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create order', 'error' => $e->getMessage()], 500);
        }
    }
}
