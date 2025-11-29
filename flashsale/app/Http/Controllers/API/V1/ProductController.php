<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Show a product with accurate available stock
     */
    public function show($id): JsonResponse
    {
        $product = Product::findOrFail($id);

        // Calculate available stock (stock - active holds)
        $activeHoldsQty = $product->holds()
                                  ->where('status', 'active')
                                  ->where('expires_at', '>', now())
                                  ->sum('qty');

        $availableStock = max($product->stock - $activeHoldsQty, 0);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'stock' => $availableStock,
        ]);
    }
}
