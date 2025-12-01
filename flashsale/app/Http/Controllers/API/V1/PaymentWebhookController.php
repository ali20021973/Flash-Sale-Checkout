<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\PaymentWebhookService;
use Exception;

class PaymentWebhookController extends Controller
{
    protected PaymentWebhookService $service;

    public function __construct(PaymentWebhookService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle incoming payment webhook
     */
    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'idempotency_key' => 'required|string',
            'order_id' => 'required|integer',
            'status' => 'required|string|in:success,failure',
        ]);

        try {
            $result = $this->service->handleWebhook($validated);

            return response()->json([
                'success' => true,
                'status' => $result
            ], 202);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
