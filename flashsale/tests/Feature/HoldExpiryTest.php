<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Hold;

class HoldExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_hold_does_not_reduce_available_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        // active hold - counted
        Hold::create(['product_id' => $product->id, 'qty' => 2, 'status' => 'active', 'expires_at' => now()->addMinutes(2)]);

        // expired hold - should not count
        Hold::create(['product_id' => $product->id, 'qty' => 1, 'status' => 'expired', 'expires_at' => now()->subMinutes(5)]);

        $this->assertEquals(3, $product->availableStock());
    }
}
