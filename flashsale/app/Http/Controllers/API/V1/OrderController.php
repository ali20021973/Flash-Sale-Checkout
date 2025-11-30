<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Hold;
use App\Models\Order;
use App\Http\Controllers\Api\V1\PaymentWebhookController;

class OrderController extends Controller
{
    /**
     * Create an order from a valid hold
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'hold_id' => 'required|integer'
        ]);

        $holdId = $request->hold_id;

        try {
            $order = DB::transaction(function () use ($holdId) {

                // Lock the hold row to avoid race conditions
                $hold = Hold::lockForUpdate()->find($holdId);

                if (!$hold) {
                    abort(404, 'Hold not found'); // Hold does not exist
                }

                if ($hold->status !== 'active') {
                    abort(409, 'Hold has already been used or released');
                }

                if ($hold->isExpired()) {
                    // Release Redis stock in case it wasn't released
                    $hold->releaseStockToRedis();
                    $hold->delete();
                    abort(409, 'Hold has expired');
                }

                // Mark hold as used
                $hold->status = 'used';
                $hold->save();

                // Create the order
                $order = Order::create([
                    'hold_id' => $hold->id,
                    'status' => 'pending_payment',
                ]);

                return $order;

            }, 5); // Retry 5 times on deadlock

            // Process any pending webhooks that may have arrived before order creation
            PaymentWebhookController::processPendingWebhooks($order);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'status' => $order->status,
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hold not found',
            ], 404);

        } catch (\Exception $e) {
            // Log full error for debugging but don't expose to client
            Log::error('Order creation failed', [
                'hold_id' => $holdId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order. Please try again.'
            ], 500);
        }
    }
}
