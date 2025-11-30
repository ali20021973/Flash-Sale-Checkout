<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\JsonResponse;

class HoldController extends Controller
{
    /**
     * Create a temporary hold
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        $productId = $request->product_id;
        $qty = $request->qty;
        $stockKey = "product_stock_{$productId}";

        try {
            // Initialize Redis stock if not exists
            if (!Redis::exists($stockKey)) {
                $product = Product::findOrFail($productId);
                Redis::set($stockKey, $product->stock);
            }

            // Redis atomic decrement
            Redis::watch($stockKey);
            $currentStock = (int) Redis::get($stockKey);

            if ($currentStock < $qty) {
                Redis::unwatch();
                return response()->json([
                    'success' => false,
                    'message' => 'Not enough stock available'
                ], 409);
            }

            $tx = Redis::multi();
            $tx->decrby($stockKey, $qty);
            $tx->exec();

            // Save hold in DB
            $hold = Hold::create([
                'product_id' => $productId,
                'qty' => $qty,
                'status' => 'active',
                'expires_at' => now()->addMinutes(2),
            ]);

            return response()->json([
                'success' => true,
                'hold_id' => $hold->id,
                'expires_at' => $hold->expires_at
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create hold',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Release expired holds and delete them
     * Call this from Scheduler every minute
     */
    public static function releaseExpiredHolds()
    {
        $now = now();

        $expiredHolds = Hold::where('status', 'active')
                            ->where('expires_at', '<', $now)
                            ->get();

        foreach ($expiredHolds as $hold) {
            $stockKey = "product_stock_{$hold->product_id}";
            Redis::incrby($stockKey, $hold->qty); // Return stock to Redis

            $hold->delete(); // Delete the expired hold from DB
        }
    }
}
