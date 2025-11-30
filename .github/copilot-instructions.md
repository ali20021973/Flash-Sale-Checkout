## Quick context for AI coding assistants

This repository is a compact Laravel 12 API that implements a flash-sale checkout flow: Product -> Hold -> Order -> Payment Webhook.
Focus on understanding durable concurrency (no oversell), short-lived holds, and idempotent webhook handling.

Key places to look:
- app/Models/Product.php (availableStock logic)
- app/Models/Hold.php (holds, expiry checks)
- app/Models/Order.php (idempotency_key and status)
- app/Models/PaymentWebhook.php (webhook payload store)
- app/Http/Controllers/API/V1/HoldController.php (DB::transaction + lockForUpdate)
- app/Http/Controllers/API/V1/OrderController.php (mark hold as used, transaction)
- app/Http/Controllers/API/V1/PaymentWebhookController.php (idempotency + release on failure)
- routes/api.php (API versioning under /api/v1)

What you must know to be productive (rules & examples):

1) API intent & flow (single-sentence summary)
- Clients create a short-lived Hold (2 minutes) for a Product, convert a valid Hold to an Order (pre-payment), and finally report payment results via an idempotent webhook.

2) Concurrency & safety rules (concrete patterns to follow)
- Use DB::transaction with lockForUpdate when touching stock/hold rows (see `HoldController::store` and `OrderController::store`).
- Controllers pass a retry limit to DB::transaction (5) to handle deadlocks — follow that pattern when modifying concurrency-related flows.

3) Idempotency & webhooks
- Payment webhooks are expected to be idempotent. The system stores `idempotency_key` on `orders` and also persists webhooks in `payment_webhooks`.
- When implementing webhook handling, check for duplicates first and avoid re-applying state transitions (see `PaymentWebhookController::handle`).

4) Data/field conventions you can rely on
- Hold lifecycle: status `active`, `used`, `expired` and `expires_at` timestamp (2 minutes when created).
- Order statuses: `pending_payment`, `paid`, `cancelled` and unique nullable `idempotency_key` field on `orders` migration.
- Product.available stock is computed as stock - sum(active holds) (see `Product::availableStock`).

5) Developer workflows & commands (copyable)
- Local setup: `composer install`, `cp .env.example .env`, update DB/REDIS config, `php artisan key:generate`.
- Refresh DB + seed: `php artisan migrate:fresh --seed` (used by the README and migrations).
- Run tests: `php artisan test`.
- Run dev environment: `composer run dev` / `composer run setup` — see `composer.json` scripts for `dev`, `setup`, `test`.

6) Environment gotchas / platform notes
- Redis is required by the app (CACHE_DRIVER=redis). The README includes Windows XAMPP instructions for adding `php_redis.dll` or `predis/predis` is installed via composer.
- Timezone note: the project uses Africa/Cairo (timestamps and expectations may assume this).

7) Code style & patterns to follow when changing the app
- Use explicit transactions and row locks for hold/order flows; avoid optimistic approaches without tests proving correctness.
- Return 409 for domain conflicts (e.g., insufficient stock or invalid/expired hold) — match existing behavior in controllers.
- Tests focus on concurrency, hold expiry, webhook idempotency and out-of-order delivery — new changes should include tests for those scenarios.

Examples to copy when implementing or fixing code
- Creating a hold: POST /api/v1/holds body { product_id, qty } → status 201 + { hold_id, expires_at }
- Creating an order: POST /api/v1/orders body { hold_id } → status 201 + { order_id, status }
- Webhook: POST /api/v1/payments/webhook body { idempotency_key, order_id, status } → idempotent update

If you need clarification or more tests/examples for a specific change, ask for the exact endpoint and I’ll generate targeted tests or a small playground route.
