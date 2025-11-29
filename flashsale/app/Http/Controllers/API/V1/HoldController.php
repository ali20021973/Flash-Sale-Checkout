<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class HoldController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        $productId = $request->product_id;
        $qty = $request->qty;

        try {
            $hold = DB::transaction(function () use ($productId, $qty) {

                $product = Product::lockForUpdate()->findOrFail($productId);

                if ($qty > $product->availableStock()) {
                    return response()->json([
                        'message' => 'Not enough stock available'
                    ], 409); // Conflict
                }

                return Hold::create([
                    'product_id' => $productId,
                    'qty' => $qty,
                    'status' => 'active',
                    'expires_at' => now()->addMinutes(2),
                ]);
            }, 5); // 5 retries if deadlock

            if ($hold instanceof JsonResponse) return $hold;

            return response()->json([
                'hold_id' => $hold->id,
                'expires_at' => $hold->expires_at
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create hold', 'error' => $e->getMessage()], 500);
        }
    }
}
