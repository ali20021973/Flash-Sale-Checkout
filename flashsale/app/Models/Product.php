<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price', 'stock'];

    // Relationship: Product has many Holds
    public function holds()
    {
        return $this->hasMany(Hold::class);
    }

    // Helper: Calculate available stock considering active holds
    public function availableStock(): int
    {
        $activeHoldsQty = $this->holds()
                               ->where('status', 'active')
                               ->where('expires_at', '>', now())
                               ->sum('qty');

        return max($this->stock - $activeHoldsQty, 0);
    }
}
