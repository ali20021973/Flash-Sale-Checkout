<?php

namespace App\Repositories;

use App\Models\Order;

class OrderRepository
{
    /**
     * Create a new order with given data
     */
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    /**
     * Find order by ID or throw ModelNotFoundException
     */
    public function findById(int $id): Order
    {
        return Order::findOrFail($id);
    }
}
