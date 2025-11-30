<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Redis;

class CacheResetServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Clear all Redis keys (or keys specific to flash sale)
        // Redis::flushall(); 
    }
}
