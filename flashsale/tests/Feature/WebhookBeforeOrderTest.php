<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookBeforeOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_for_missing_order_returns_404_current_behavior(): void
    {
        $payload = [
            'idempotency_key' => 'no-order-1',
            'order_id' => 999999,
            'status' => 'success'
        ];

        $this->postJson('/api/v1/payments/webhook', $payload)->assertStatus(404);
    }

    /**
     * Recommended improvement: accept and persist webhooks even if order doesn't exist yet, then reconcile later.
     * This test documents current behaviour and the recommended alternative should be accompanied by a new
     * PaymentWebhook model create + a queue worker that reconciles records.
     */
}
