<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Repositories\HoldRepository;
use App\Services\PaymentWebhookService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class OrderService
{
    protected PaymentWebhookService $webhookService;
    protected OrderRepository $orderRepo;
    protected HoldRepository $holdRepo;

    public function __construct(
        OrderRepository $orderRepo,
        HoldRepository $holdRepo,
        PaymentWebhookService $webhookService
    ) {
        $this->orderRepo = $orderRepo;
        $this->holdRepo = $holdRepo;
        $this->webhookService = $webhookService;
    }

    /**
     * Create an order from a hold safely
     */
    public function createOrderFromHold(int $holdId)
    {
        try {
                $order = DB::transaction(function () use ($holdId) {
                $hold = $this->holdRepo->lockForUpdate($holdId);

                if (!$hold) throw new ModelNotFoundException('Hold not found.');
                if ($hold->status !== 'active') throw new Exception('Hold already used.');
                if ($hold->isExpired()) {
                    $hold->releaseStockToRedis();
                    $hold->delete();
                    throw new Exception('Hold has expired.');
                }

                $hold->status = 'used';
                $hold->save();

                $order = $this->orderRepo->create([
                    'hold_id' => $hold->id,
                    'status' => 'pending_payment',
                ]);

                return $order;

            }, 5); // Retry 5 times on deadlock

             // Process any pending webhooks immediately
                  
            $this->webhookService->processPendingWebhooks($order);

            
            $order->refresh();
            return $order;

        } catch (ModelNotFoundException $e) {
            throw $e;

        } catch (Exception $e) {
            Log::error('Order creation failed', [
                'hold_id' => $holdId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to create order. Please try again.');
        }
    }
}
