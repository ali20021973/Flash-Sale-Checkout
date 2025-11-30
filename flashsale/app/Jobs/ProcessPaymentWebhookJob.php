<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhookJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        // Basic validation of payload inside job
        if (
            empty($this->data['order_id']) ||
            empty($this->data['idempotency_key']) ||
            !isset($this->data['status']) ||
            !in_array($this->data['status'], ['success','failure'])
        ) {
            Log::warning('Invalid webhook payload', ['data' => $this->data]);
            return; // skip invalid payload
        }

        $order = Order::find($this->data['order_id']);
        if (!$order) {
            // Optionally re-queue or store again in Redis if order not yet created
            Redis::rpush("pending_webhooks:order:{$this->data['order_id']}", json_encode($this->data));
            Log::info("Order not found, webhook re-queued", ['order_id' => $this->data['order_id']]);
            return;
        }

        $key = "lock:order:{$order->id}";

        Redis::funnel($key)->limit(1)->block(5)->then(function () use ($order) {

            try {
                DB::transaction(function () use ($order) {
                    $idempotencyKey = $this->data['idempotency_key'];
                    $status = $this->data['status'];

                    // Skip if already processed
                    if ($order->idempotency_key === $idempotencyKey) {
                        return;
                    }

                    $order->idempotency_key = $idempotencyKey;

                    if ($status === 'success') {
                        $order->status = 'paid';
                    } else {
                        $order->status = 'cancelled';

                        // Release hold safely
                        if ($order->hold) {
                            $order->hold->status = 'active';
                            $order->hold->save();
                        }
                    }

                    $order->save();
                });

            } catch (\Exception $e) {
                Log::error('Failed to process webhook inside job', [
                    'order_id' => $order->id,
                    'data' => $this->data,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
