<?php

namespace App\Services;

use App\Repositories\PaymentWebhookRepository;
use App\Models\Order;
use Illuminate\Support\Facades\Redis;
use App\Jobs\ProcessPaymentWebhookJob;
use Illuminate\Support\Facades\Log;
use Exception;


class PaymentWebhookService
{
    protected PaymentWebhookRepository $repo;

    public function __construct(PaymentWebhookRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Handle incoming webhook
     */
    public function handleWebhook(array $data): string
    {
        $orderId = $data['order_id'] ?? null;

        Log::info('Webhook received', [
            'order_id' => $orderId,
            'payload' => $data,
        ]);

        $order = $this->repo->findOrder($orderId);

        if (!$order) {
            $key = "pending_webhooks:order:{$orderId}";
            Redis::rpush($key, json_encode($data));

            Log::warning('Order not found → webhook queued to Redis', [
                'key' => $key,
                'payload' => $data
            ]);

            return 'queued_to_redis';
        }

        try {
            ProcessPaymentWebhookJob::dispatch($data);
            return 'queued_job';
        } catch (Exception $e) {
            Log::error('Failed to dispatch webhook job', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to queue webhook job.');
        }
    }

    /**
     * Process pending webhooks for an order
     */
   public function processPendingWebhooks(Order $order): void
{
    $key = "pending_webhooks:order:{$order->id}";

    while ($data = Redis::lpop($key)) {
        $payload = json_decode($data, true);

        if (!$payload || empty($payload['idempotency_key']) || empty($payload['status'])) {
            continue;
        }

        // 🔹 Dispatch synchronously to guarantee update before returning
        ProcessPaymentWebhookJob::dispatchSync($payload);
    }
}

}
