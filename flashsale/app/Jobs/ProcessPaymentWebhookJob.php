<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
        $order = Order::find($this->data['order_id']);
        if (!$order) return;

        $key = "lock:order:{$order->id}";

        Redis::funnel($key)->limit(1)->block(5)->then(function () use ($order) {

            DB::transaction(function () use ($order) {

                $idempotencyKey = $this->data['idempotency_key'];
                $status = $this->data['status'];

                // already processed
                if ($order->idempotency_key === $idempotencyKey) {
                    return;
                }

                $order->idempotency_key = $idempotencyKey;

                if ($status === 'success') {
                    $order->status = 'paid';
                } else {
                    $order->status = 'cancelled';
                    if ($order->hold) {
                        $order->hold->status = 'active';
                        $order->hold->save();
                    }
                }

                $order->save();
            });

        });
    }
}
