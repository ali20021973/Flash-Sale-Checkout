# Flash-Sale Checkout — API (Laravel 12)

This repository is a compact backend API used to demonstrate a safe, high-concurrency "flash sale" checkout workflow: Products -> temporary Holds -> Orders -> Payment Webhooks.

Summary (one line)
- Ensures no oversell by locking product rows during hold creation and converting holds into pre-payment orders. Webhooks are idempotent and drive final payment outcome.

Important assumptions & invariants
- Product stock is authoritative; availability = product.stock - sum(active holds).
- Holds are short-lived (expires_at set to now + 2 minutes), status values: `active`, `used`, `expired`.
- Orders are created only from valid, active holds and start as `pending_payment`. Order statuses: `pending_payment`, `paid`, `cancelled`.
- Webhooks must be treated idempotently. The system records `idempotency_key` at the Order level and the DB schema includes a `payment_webhooks` table (suggested to persist raw payloads).

Quick setup (local / dev)
1. Install dependencies

```powershell
composer install
npm install
```

2. Copy environment file and generate app key

```powershell
copy .env.example .env
php artisan key:generate
```

3. Configure database and Redis in `.env` (MySQL + Redis expected). On Windows + XAMPP follow README's php_redis.dll steps if enabling PHP Redis extension.

4. Run migrations + seed demo data

```powershell
php artisan migrate:fresh --seed
```

5. Run server

```powershell
php artisan serve
# API base: http://127.0.0.1:8000/api/v1
```

Running tests
- Unit + Feature tests: `php artisan test`.
- This repository uses PHPUnit; tests live under `tests/Feature` and `tests/Unit`.

Where to inspect runtime logs, metrics and cache
- Laravel logs: `storage/logs/laravel.log`.
- Redis is required for cache and concurrency flagging (CACHE_DRIVER and QUEUE_CONNECTION set to redis). Validate a working Redis server and PHP redis/predis installation.

Folder structure (high level)
- app/
  - Models/: Product, Hold, Order, PaymentWebhook (relationships + helpers live here)
  - Http/Controllers/API/V1/: ProductController, HoldController, OrderController, PaymentWebhookController
  - Repositories/ & Services/: present — used for encapsulating business logic (see app/Repositories and app/Services)
- database/: migrations + seeders used to build demo data
- routes/api.php: API (prefixed with /api/v1)

Developer-convention notes (follow these)
- Use DB::transaction + lockForUpdate for stock/hold modifications (HoldController::store, OrderController::store). Keep the retry count used elsewhere (transaction(..., 5)).
- Return HTTP 409 for domain conflicts (insufficient stock, expired/invalid hold).
- Persist idempotency keys and avoid re-applying state transitions when the same webhook is replayed.

Current limitations / recommended improvements
- Webhook handling: the controller returns 404 if the order doesn't exist. For robust environments where webhooks may arrive before order creation, consider persisting webhook payloads (`payment_webhooks`) and/or queueing a retry processor to reconcile webhooks with orders once created. The DB schema already includes a `payment_webhooks` table; the controller should record incoming webhooks there and implement idempotent processing.
- Add tests that simulate real concurrent requests (integration-level tests using multiple worker processes) to prove no oversell under high pressure.

If you want I can: add end-to-end concurrency integration tests, switch webhook handling to store-first-then-process, and add structured logging/metrics for high-concurrency observability.

---

Generated / suggested test files (examples included under tests/Feature)
- HoldConcurrencyTest.php — asserts no oversell when creating multiple holds against limited stock.
- HoldExpiryTest.php — verifies expired holds return availability.
- WebhookIdempotencyTest.php — verifies processing the same webhook twice does not double-apply.
- WebhookBeforeOrderTest.php — documents expected behavior and recommended fix (persist webhook and process later). 


```markdown
# Flash-Sale Checkout API (Laravel 12)

**Project Summary:**  
A small API for selling a limited-stock product during a flash sale. It handles **high concurrency without overselling**, supports **temporary holds**, **pre-payment orders**, and an **idempotent payment webhook**. No UI is included.

---

## **Tech Stack**

- **Laravel:** 12.40.2  
- **PHP:** 8.2.12  
- **Database:** MySQL (InnoDB)  
- **Cache / Concurrency:** Redis (PHP Redis extension)  
- **Testing:** Postman or automated PHPUnit tests  

---
````
## **Project Structure**

```bash

app/
├─ Models/
│  ├─ Product.php
│  ├─ Hold.php
│  ├─ Order.php
│  └─ Payment.php
├─ Http/
│  └─ Controllers/
│     └─ Api/
│        └─ V1/
│           ├─ ProductController.php
│           ├─ HoldController.php
│           ├─ OrderController.php
│           └─ PaymentWebhookController.php
database/
├─ factories/
│  └─ ProductFactory.php, HoldFactory.php, OrderFactory.php, PaymentFactory.php
├─ migrations/
│  └─ xxxx_create_products_table.php, xxxx_create_holds_table.php, ...
└─ seeders/
└─ ProductSeeder.php, HoldSeeder.php, OrderSeeder.php, PaymentSeeder.php
routes/
└─ api.php

````

---

## **Setup Instructions (Local)**

1. **Clone repository**

```bash
git clone https://github.com/ali20021973/Flash-Sale-Checkout
cd flashsale
````

2. **Install dependencies**

```bash
composer install

```

3. **Configure environment**

```bash
cp .env.example .env
```

Update `.env` with your database and Redis:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306

security variables
DB_DATABASE*******
DB_USERNAME*******
DB_PASSWORD*******

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Africa/Cairo
```

4. **Install Redis extension (Windows XAMPP)**

* Download `php_redis.dll` matching PHP 8.2 TS x64 from [PECL](https://windows.php.net/downloads/pecl/releases/redis/)
* Copy to `C:\xampp\php\ext`
* Add to `php.ini`:

```ini
extension=redis
```

* Restart Apache
* Verify installation:

```bash
php -m | findstr "redis"
```

5. **Run migrations & seed database**

```bash
php artisan migrate:fresh --seed
```

* Creates all tables and seeds realistic mock data for products, holds, and orders.

6. **Start Laravel server**

```bash
php artisan serve
```

* API is available at:

```
http://127.0.0.1:8000/api/v1
```

---

## **API Endpoints**

### **1. Get Product**

```http
GET /api/v1/products/{id}
```

* Returns product info and accurate available stock.

---

### **2. Create Hold**

```http
POST /api/v1/holds
Content-Type: application/json
```

Body:

```json
{
    "product_id": 1,
    "qty": 2
}
```

* Creates a temporary hold (~2 minutes).
* Response:

```json
{
    "hold_id": 7,
    "expires_at": "2025-11-29T13:45:00"
}
```

---

### **3. Create Order**

```http
POST /api/v1/orders
Content-Type: application/json
```

Body:

```json
{
    "hold_id": 7
}
```

* Converts a valid hold to a pre-payment order.

---

### **4. Payment Webhook**

```http
POST /api/v1/payments/webhook
Content-Type: application/json
```

Body:

```json
{
    "idempotency_key": "123e4567-e89b-12d3-a456-426614174000",
    "order_id": 3,
    "status": "success" // or "failure"
}
```

* Updates order to `paid` or `cancelled` safely.
* Handles duplicate and out-of-order requests (idempotent).

---

## **Testing**

* Use **Postman** to test API endpoints.
* Automated tests:

```bash
php artisan test
```

**Test cases include:**

* Parallel hold attempts at stock boundary (no oversell)
* Hold expiry returns availability
* Webhook idempotency (same key repeated)
* Webhook arriving before order creation

---


##requres 
composer require predis/predis

## **Notes**

* All timestamps are in **Africa/Cairo** timezone.
* Redis is required for cache and concurrency safety.
* Logs and metrics are visible in:

```
storage/logs/laravel.log
```

---

## **Next Steps for Production**

* Implement **atomic transactions** for hold + order creation to prevent overselling.
* Schedule **background jobs** for hold expiry (~2 minutes).
* Add structured logging for high-concurrency monitoring.
* Optimize caching for burst traffic performance.

```

---



this what i do new 

RefreshFlashSaleCache extends Command
{
    protected $signature = 'flashsale:refresh-cache';
    protected $description = 'Refresh flash sale stock cache in Redis every minute';


This solves Out-of-Order delivery:
Scenario:

Webhook arrives at 10:00:00

Order created at 10:00:01

Webhook is saved in Redis (because order did not exist)

After creating the order → we process the stored webhook

Your system now handles this perfectly.