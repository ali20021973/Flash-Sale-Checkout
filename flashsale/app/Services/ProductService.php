<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Redis;
use stdClass;

class ProductService
{
    protected ProductRepository $repo;
    protected int $redisTtl;

    public function __construct(ProductRepository $repo)
    {
        $this->repo = $repo;
        $this->redisTtl = 86400; // 24 hours 
    }

    /**
     * Return a simple DTO (stdClass) with product fields + available_stock
     * - Keeps Eloquent models inside repository layer
     */
    public function getProductWithAvailableStock(int $id): stdClass
    {
        // 1) Get product (repository throws ModelNotFoundException if not exists)
        $product = $this->repo->findById($id);

      
        $stockKey = $this->stockKey($product->id);

        
        $available = Redis::get($stockKey);

        if ($available === null) {
          
            $set = Redis::setnx($stockKey, $product->stock);

           
            Redis::expire($stockKey, $this->redisTtl);

            if ($set) {
                $available = $product->stock;
            } else {
                // another process created the key; read it again
                $available = (int) Redis::get($stockKey);
            }
        } else {
            $available = (int) $available;
        }

        $dto = new stdClass();
        $dto->id = $product->id;
        $dto->name = $product->name;
        $dto->price = $product->price;
        $dto->available_stock = $available;

        return $dto;
    }

    protected function stockKey(int $productId): string
    {
        return "product_stock:{$productId}";
    }
}
