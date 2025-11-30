<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\Product;

class RefreshFlashSaleCache extends Command
{
    protected $signature = 'flashsale:refresh-cache';
    protected $description = 'Refresh flash sale stock cache in Redis every minute';

    public function handle()
    {
        $products = Product::all();

        foreach ($products as $product) {
            $activeHoldsQty = $product->holds()
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->sum('qty');

            $availableStock = max($product->stock - $activeHoldsQty, 0);

            // Store in Redis (atomic)
            Redis::set('flashsale_product_stock_' . $product->id, $availableStock);
        }

        $this->info('Flash sale stock cache refreshed successfully.');
    }
}
