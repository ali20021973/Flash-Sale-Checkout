<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hold;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredHolds extends Command
{
    protected $signature = 'flashsale:release-expired';
    protected $description = 'Release expired holds (return stock to Redis and mark hold expired)';

    public function handle()
    {
        $now = now();
        $expired = Hold::where('status', 'active')->where('expires_at', '<=', $now)->get();

        foreach ($expired as $hold) {
            try {
                // mark expired
                $hold->status = 'expired';
                $hold->save();

                // return to Redis stock
                $key = "product_stock_{$hold->product_id}";
                Redis::incrby($key, $hold->qty);

                Log::info('Released expired hold', ['hold_id' => $hold->id, 'product_id' => $hold->product_id, 'qty' => $hold->qty]);
            } catch (\Throwable $e) {
                Log::error('Failed to release hold', ['hold_id' => $hold->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info('Expired holds processed: ' . $expired->count());
    }
}
