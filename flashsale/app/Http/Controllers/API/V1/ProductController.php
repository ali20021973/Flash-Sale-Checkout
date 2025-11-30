<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;



class ProductController extends Controller
{
    /**
     * GET /api/products/{id}
     * Returns the product with accurate and up-to-date available stock
     */
    public function show($id): JsonResponse
    {
        
        //Scheduler calls releaseExpiredHolds() every minute but to be in saveside call releaseExpiredHolds during min.
        \App\Http\Controllers\API\V1\HoldController::releaseExpiredHolds();
        try {
            $stockKey = "product_stock_{$id}";

            // Initialize stock in Redis if not exists
            if (!Redis::exists($stockKey)) {
                $product = Product::findOrFail($id);
                Redis::set($stockKey, $product->stock);
            }

            $availableStock = (int) Redis::get($stockKey);

            $product = Product::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'available_stock' => $availableStock,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
