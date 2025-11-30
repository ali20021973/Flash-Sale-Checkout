<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

class ProductController extends Controller
{
    /**
     * Show a product with accurate available stock
     */
    public function show($id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            // Use Redis key for stock
            $stockKey = "product_stock_{$id}";

            // Initialize Redis stock if not exists
            if (!Redis::exists($stockKey)) {
                Redis::set($stockKey, $product->stock,'EX',60);
            }

            $availableStock = Redis::get($stockKey);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock' => (int) $availableStock,
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
