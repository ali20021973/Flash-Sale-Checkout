<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
            HoldSeeder::class,
            OrderSeeder::class,
            PaymentWebhookSeeder::class,
        ]);
    }
}

// ------------------- Product SEEDER -------------------
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        \DB::table('products')->insert([
            [ 'name' => 'iPhone 15 Pro Max', 'price' => 54999, 'stock' => 30, 'created_at' => now(), 'updated_at' => now() ],
            [ 'name' => 'Samsung S24 Ultra', 'price' => 42000, 'stock' => 25, 'created_at' => now(), 'updated_at' => now() ],
            [ 'name' => 'Xiaomi 14 Pro', 'price' => 32000, 'stock' => 40, 'created_at' => now(), 'updated_at' => now() ],
            [ 'name' => 'Redmi Note 13 Pro', 'price' => 15000, 'stock' => 50, 'created_at' => now(), 'updated_at' => now() ],
            [ 'name' => 'Realme GT 6', 'price' => 18000, 'stock' => 22, 'created_at' => now(), 'updated_at' => now() ],
            [ 'name' => 'Oppo Reno 11', 'price' => 21000, 'stock' => 19, 'created_at' => now(), 'updated_at' => now() ],
        ]);
    }
}

// ------------------- HOLD SEEDER -------------------
class HoldSeeder extends Seeder
{
    public function run(): void
    {
        \DB::table('holds')->insert([
            [
                'product_id' => 1,
                'qty' => 1,
                'status' => 'active',
                'expires_at' => Carbon::now()->addMinutes(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 2,
                'qty' => 2,
                'status' => 'active',
                'expires_at' => Carbon::now()->addMinutes(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

// ------------------- ORDER SEEDER -------------------
class OrderSeeder extends Seeder
{
    public function run(): void
    {
        \DB::table('orders')->insert([
            [
                'hold_id' => 1,
                'status' => 'pending_payment',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'hold_id' => 2,
                'status' => 'pending_payment',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

// ------------------- PAYMENT WEBHOOK SEEDER -------------------
class PaymentWebhookSeeder extends Seeder
{
    public function run(): void
    {
        \DB::table('payment_webhooks')->insert([
            [
                'order_id' => 1,
                'idempotency_key' => Str::uuid()->toString(),
                'payload' => json_encode(['status' => 'success']),
                'created_at' => now(),
            ],
            [
                'order_id' => 2,
                'idempotency_key' => Str::uuid()->toString(),
                'payload' => json_encode(['status' => 'failed']),
                'created_at' => now(),
            ],
        ]);
    }
}
