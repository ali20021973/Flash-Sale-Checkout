<?php

namespace App\Services;

use App\Repositories\HoldRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Exception;

class HoldService
{
    protected HoldRepository $holdRepo;
    protected ProductRepository $productRepo;
    protected int $holdExpiryMinutes = 2;

    public function __construct(HoldRepository $holdRepo, ProductRepository $productRepo)
    {
        $this->holdRepo = $holdRepo;
        $this->productRepo = $productRepo;
    }

    /**
     * Create a temporary hold on a product
     */
    public function createHold(int $productId, int $qty)
    {
        $stockKey = "product_stock_{$productId}";

        // Initialize Redis stock if not exists
        if (!Redis::exists($stockKey)) {
            $product = $this->productRepo->findById($productId);
            Redis::set($stockKey, $product->stock);
        }

        // Atomic decrement with WATCH
        Redis::watch($stockKey);
        $currentStock = (int) Redis::get($stockKey);

        if ($currentStock < $qty) {
            Redis::unwatch();
            throw new Exception('Not enough stock available');
        }

        $tx = Redis::multi();
        $tx->decrby($stockKey, $qty);
        $execResult = $tx->exec();

        if (!$execResult) {
            // Retry failed due to concurrency; throw exception or retry externally
            throw new Exception('Stock contention occurred. Please try again.');
        }

        // Save hold in DB
        return $this->holdRepo->create([
            'product_id' => $productId,
            'qty' => $qty,
            'status' => 'active',
            'expires_at' => now()->addMinutes($this->holdExpiryMinutes),
        ]);
    }

    /**
     * Release expired holds and return stock
     */
    public function releaseExpiredHolds(): void
    {
        $expiredHolds = $this->holdRepo->getExpiredActiveHolds();

        foreach ($expiredHolds as $hold) {
            try {
                $stockKey = "product_stock_{$hold->product_id}";
                Redis::incrby($stockKey, $hold->qty); // Return stock to Redis
                $hold->delete(); // Remove expired hold
            } catch (\Throwable $e) {
                Log::error('Failed to release expired hold', [
                    'hold_id' => $hold->id,
                    'exception' => $e->getMessage()
                ]);
            }
        }
    }
}
