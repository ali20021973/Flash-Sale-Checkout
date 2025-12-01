<?php

namespace App\Repositories;

use App\Models\Order;

class PaymentWebhookRepository
{
    /**
     * Find order by ID
     */
    public function findOrder(int $orderId): ?Order
    {
        return Order::find($orderId);
    }
}
