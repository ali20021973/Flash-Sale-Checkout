<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\HoldService;
use Exception;

class HoldController extends Controller
{
    protected HoldService $holdService;

    public function __construct(HoldService $holdService)
    {
        $this->holdService = $holdService;
    }

    /**
     * Create a temporary hold
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'qty' => 'required|integer|min:1',
        ]);

        $productId = $request->product_id;
        $qty = $request->qty;

        try {
            $hold = $this->holdService->createHold($productId, $qty);

            return response()->json([
                'success' => true,
                'hold_id' => $hold->id,
                'expires_at' => $hold->expires_at,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 409);
        }
    }

    /**
     * Scheduler can call this to release expired holds
     */
    public function release(): JsonResponse
    {
        try {
            $this->holdService->releaseExpiredHolds();
            return response()->json(['success' => true], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
}
