<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    /**
     * Return Eloquent product model or throw ModelNotFoundException
     */
    public function findById(int $id): Product
    {
       
        return Product::findOrFail($id);
    }
}
