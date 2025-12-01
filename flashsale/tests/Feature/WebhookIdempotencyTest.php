<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Hold;
use App\Models\Order;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_processing_same_webhook_twice_is_idempotent(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $hold = Hold::create(['product_id' => $product->id, 'qty' => 1, 'status' => 'active', 'expires_at' => now()->addMinutes(2)]);

        $order = Order::create(['hold_id' => $hold->id, 'status' => 'pending_payment']);

        $payload = [
            'idempotency_key' => 'abc-123',
            'order_id' => $order->id,
            'status' => 'success'
        ];

        $this->postJson('/api/v1/payments/webhook', $payload)->assertStatus(200);

        // second call with same idempotency_key should be accepted but make no further changes
        $this->postJson('/api/v1/payments/webhook', $payload)->assertStatus(200);

        $order->refresh();
        $this->assertEquals('paid', $order->status);
        $this->assertEquals('abc-123', $order->idempotency_key);
    }
}
