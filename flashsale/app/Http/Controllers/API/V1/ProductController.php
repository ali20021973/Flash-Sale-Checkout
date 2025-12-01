<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/products/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $productDto = $this->service->getProductWithAvailableStock($id);

            return response()->json([
                'success' => true,
                'data' => new ProductResource($productDto),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);

        } catch (\Throwable $e) {
            // Generic safe error
            return response()->json(['success' => false, 'message' => 'Unable to process request.'], 500);
        }
    }
}
