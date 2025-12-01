<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;

class HoldConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simulate the boundary conditions for holds so we don't oversell.
     * Note: this is a sequential test example — for realistic concurrency use multiple worker processes
     * or an integration test that fires simultaneous HTTP requests to the API.
     */
    public function test_prevents_oversell_when_stock_limited(): void
    {
        $product = Product::factory()->create(['stock' => 1]);

        // First hold (should succeed)
        $response1 = $this->postJson('/api/v1/holds', ['product_id' => $product->id, 'qty' => 1]);
        $response1->assertStatus(201);

        // Second hold should be rejected (not enough available stock)
        $response2 = $this->postJson('/api/v1/holds', ['product_id' => $product->id, 'qty' => 1]);
        $response2->assertStatus(409);
    }
}
